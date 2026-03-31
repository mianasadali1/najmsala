<?php

namespace App\Services;

use App\Enums\Ask;
use App\Enums\Status;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductCustomImportService
{
    public function preview($file): array
    {
        $rows = $this->parseExcel($file);
        return array_map(function ($row) {
            $v = $this->validateRow($row);
            $row['errors']   = $v['errors'];
            $row['warnings'] = $v['warnings'];
            $row['status']   = empty($v['errors']) ? 'valid' : 'error';
            return $row;
        }, $rows);
    }

    public function import($file): array
    {
        $rows    = $this->parseExcel($file);
        $results = [];

        foreach ($rows as $row) {
            $v = $this->validateRow($row);
            if (!empty($v['errors'])) {
                $row['status']  = 'error';
                $row['message'] = implode(', ', $v['errors']);
                $results[]      = $row;
                continue;
            }
            try {
                $this->saveProduct($row);
                $row['status']  = 'imported';
                $row['message'] = 'OK';
            } catch (\Exception $e) {
                $row['status']  = 'error';
                $row['message'] = $e->getMessage();
            }
            $results[] = $row;
        }

        return $results;
    }

    private function parseExcel($file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            return [];
        }

        $headers = array_map(
            fn($h) => strtolower(str_replace([' ', '-'], '_', trim((string) $h))),
            $rows[0]
        );

        $data = [];
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                continue;
            }
            $mapped = [];
            foreach ($headers as $idx => $header) {
                $mapped[$header] = isset($row[$idx]) ? trim((string) $row[$idx]) : '';
            }
            $mapped['_row'] = $i + 1;
            $data[]         = $mapped;
        }

        return $data;
    }

    private function validateRow(array $row): array
    {
        $errors = [];
        if (empty($row['name'] ?? '')) {
            $errors[] = 'Name is required';
        }
        if (!isset($row['price']) || !is_numeric($row['price'])) {
            $errors[] = 'Price must be numeric';
        }
        return ['errors' => $errors, 'warnings' => []];
    }

    private function saveProduct(array $data): void
    {
        // Step 1: save product data in a transaction
        $product = DB::transaction(function () use ($data) {
            // Resolve or create category
            $categoryId = null;
            if (!empty($data['category'])) {
                $cat = ProductCategory::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($data['category']) . '%'])->first();
                if (!$cat) {
                    $cat = ProductCategory::create([
                        'name'   => $data['category'],
                        'slug'   => Str::slug($data['category']),
                        'status' => Status::ACTIVE,
                    ]);
                }
                $categoryId = $cat->id;
            }

            // Resolve or create brand
            $brandId = null;
            if (!empty($data['brand'])) {
                $brand = ProductBrand::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($data['brand']) . '%'])->first();
                if (!$brand) {
                    $brand = ProductBrand::create([
                        'name'   => $data['brand'],
                        'slug'   => Str::slug($data['brand']),
                        'status' => Status::ACTIVE,
                    ]);
                }
                $brandId = $brand->id;
            }

            // Unique slug
            $slug         = Str::slug($data['name']);
            $originalSlug = $slug;
            $counter      = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            // Status
            $avail  = strtolower($data['availability'] ?? 'active');
            $status = in_array($avail, ['inactive', 'no', '0']) ? Status::INACTIVE : Status::ACTIVE;

            // Prices — `discount` column is stored as percentage (see ProductAdminResource, admin "discount %").
            $sellingPrice = (float) ($data['price'] ?? 0);
            $discount     = 0;
            $discountedRaw = $data['discounted_price'] ?? '';
            $discountedRaw = is_string($discountedRaw) ? str_replace(',', '', trim($discountedRaw)) : $discountedRaw;
            if ($discountedRaw !== '' && is_numeric($discountedRaw) && $sellingPrice > 0) {
                $discountedPrice = (float) $discountedRaw;
                if ($discountedPrice >= 0 && $discountedPrice < $sellingPrice) {
                    $discount = (($sellingPrice - $discountedPrice) / $sellingPrice) * 100;
                }
            }

            // Offer window so `is_offer` / storefront treat the discount as active (fixed Excel price → % in DB).
            $offerStartDate = null;
            $offerEndDate   = null;
            if ($discount > 0) {
                $offerStartDate = Carbon::yesterday()->startOfDay()->format('Y-m-d H:i:s');
                $offerEndDate   = Carbon::now()->addYear()->format('Y-m-d H:i:s');
            }

            $product = Product::create([
                'name'                => $data['name'],
                'slug'                => $slug,
                'sku'                 => strtoupper(Str::random(8)),
                'product_category_id' => $categoryId,
                'product_brand_id'    => $brandId,
                'unit_id'             => 10,
                'selling_price'       => $sellingPrice,
                'variation_price'     => $sellingPrice,
                'buying_price'        => $sellingPrice,
                'discount'            => round($discount, 6),
                'offer_start_date'    => $offerStartDate,
                'offer_end_date'      => $offerEndDate,
                'description'         => $data['description'] ?? '',
                'status'              => $status,
                'can_purchasable'     => Ask::YES,
                'show_stock_out'      => Ask::NO,
                'refundable'          => Ask::YES,
                'thumbnail_url'       => !empty($data['thumbnail']) ? $data['thumbnail'] : null,
                'image1_url'          => !empty($data['image1'])    ? $data['image1']    : null,
                'image2_url'          => !empty($data['image2'])    ? $data['image2']    : null,
            ]);

            // Tags from size, color, tag columns
            foreach (['size', 'color', 'tag'] as $col) {
                if (!empty($data[$col])) {
                    foreach (explode(',', $data[$col]) as $t) {
                        $t = trim($t);
                        if ($t !== '') {
                            ProductTag::create(['product_id' => $product->id, 'name' => $t]);
                        }
                    }
                }
            }

            // Stock
            $qty = $this->parseStockQty($data['stock'] ?? '0');
            Stock::create([
                'model_type' => Product::class,
                'model_id'   => $product->id,
                'item_type'  => Product::class,
                'item_id'    => $product->id,
                'product_id' => $product->id,
                'price'      => $sellingPrice,
                'quantity'   => $qty,
                'discount'   => 0,
                'tax'        => 0,
                'subtotal'   => $sellingPrice * $qty,
                'total'      => $sellingPrice * $qty,
                'sku'        => $product->sku,
                'status'     => Status::ACTIVE,
            ]);

            return $product;
        });

    }

    private function parseStockQty(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        // "SOAP=001" → 1
        if (str_contains($value, '=')) {
            $parts = explode('=', $value, 2);
            $num   = ltrim(trim($parts[1]), '0') ?: '0';
            return (int) $num;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        preg_match('/(\d+)/', $value, $m);
        return isset($m[1]) ? (int) $m[1] : 0;
    }
}
