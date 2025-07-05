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
        Schema::create('shipments', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->Integer('order_id');
            $table->Integer('sales_task_id');
            $table->date('perkiraan_pengiriman')->nullable();
            $table->enum('status_pengiriman',['diproses','dikirim','diterima'])->nullable()->default('diproses');
            $table->decimal('biaya_pengiriman',8,2);
            $table->timestamps();
        
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('sales_task_id')->references('id')->on('sales_tasks')->onDelete('cascade');
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
