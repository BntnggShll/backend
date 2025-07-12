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
            $table->unsignedInteger('harga_jual')->default(0);
            $table->integer('quantity');
            $table->enum('status',['in','out']);
            $table->timestamps();

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
