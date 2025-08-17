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
        Schema::create('sales_stocks', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('sales_id');
            $table->integer('product_unit_id');
            $table->integer('quantity');
            $table->enum('status',['in','out']);
            $table->integer('stock_movement_id')->nullable();
            $table->timestamps();
            
            $table->foreign('stock_movement_id')->references('id')->on('stock_movements')->onDelete('cascade');
            $table->foreign('sales_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_unit_id')->references('id')->on('product_units')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_stocks');
    }
};
