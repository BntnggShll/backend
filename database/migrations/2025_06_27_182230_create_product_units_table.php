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
        Schema::create('product_units', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->Integer('product_id');
            $table->Integer('unit_id');
            $table->Integer('parent_id')->nullable();
            $table->unsignedInteger('conversion_rate')->default(1);
            $table->decimal('harga_jual', 15, 2);
            $table->boolean('is_base_unit')->default(false);
            $table->integer('min_stock_level')->default(0);
            $table->timestamps();


            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('product_units')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
