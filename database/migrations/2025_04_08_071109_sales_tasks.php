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
        Schema::create('sales_tasks', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->Integer('sales_id');
            $table->enum('jenis_tugas', ['pengantaran', 'promosi']);
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['diproses', 'selesai'])->default('diproses');
            $table->timestamps();
        
            $table->foreign('sales_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
