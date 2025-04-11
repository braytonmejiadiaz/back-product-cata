<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('product_limit')->nullable()->after('mercadopago_plan_id');
            $table->boolean('is_free')->default(false)->after('product_limit');
        });

        // Insertar plan gratis
        DB::table('plans')->insert([
            'name' => 'Gratis',
            'price' => 0,
            'description' => 'Plan gratuito con limitaciones',
            'mercadopago_plan_id' => null,
            'product_limit' => 3, // Máximo 3 productos
            'is_free' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['product_limit', 'is_free']);
        });
    }
};
