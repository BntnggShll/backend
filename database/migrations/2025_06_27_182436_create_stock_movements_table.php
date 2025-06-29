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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('product_unit_id');
            $table->tinyInteger('quantity'); // Positive for IN, Negative for OUT
            $table->enum('type', ['in', 'out']);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('product_unit_id')->references('id')->on('product_units')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
