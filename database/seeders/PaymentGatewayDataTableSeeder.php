<?php

namespace Database\Seeders;

use App\Enums\GatewayMode;
use App\Enums\Activity;
use App\Models\GatewayOption;
use App\Models\PaymentGateway;
use Dipokhalder\EnvEditor\EnvEditor;
use Illuminate\Database\Seeder;

class PaymentGatewayDataTableSeeder extends Seeder
{
    /**
     * Keep credentials out of git.
     * We only set demo-friendly mode/status defaults; real gateway keys must be configured elsewhere.
     */
    private array $gatewaySlugs = [
        'paypal',
        'stripe',
        'flutterwave',
        'paystack',
        'sslcommerz',
        'mollie',
        'senangpay',
        'bkash',
        'paytm',
        'razorpay',
        'mercadopago',
        'cashfree',
        'payfast',
        'skrill',
        'phonepe',
        'iyzico',
        'pesapal',
        'midtrans',
        'twocheckout',
        'myfatoorah',
        'easypaisa',
    ];

    public function run(): void
    {
        $envService = new EnvEditor();

        if (! $envService->getValue('DEMO')) {
            return;
        }

        foreach ($this->gatewaySlugs as $slug) {
            $payment = PaymentGateway::where(['slug' => $slug])->first();
            if ($payment) {
                $payment->status = Activity::ENABLE;
                $payment->save();
            }

            // Demo defaults for gateway mode/status options.
            GatewayOption::where('option', 'LIKE', $slug . '%_mode')->update([
                'value' => GatewayMode::SANDBOX,
            ]);

            GatewayOption::where('option', 'LIKE', $slug . '%_status')->update([
                'value' => Activity::ENABLE,
            ]);
        }
    }
}

