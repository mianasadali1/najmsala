<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('thumbnail_url')->nullable()->after('description');
            $table->string('image1_url')->nullable()->after('thumbnail_url');
            $table->string('image2_url')->nullable()->after('image1_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_url', 'image1_url', 'image2_url']);
        });
    }
};
