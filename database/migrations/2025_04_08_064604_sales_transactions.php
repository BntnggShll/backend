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
        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id(); // 
            $table->unsignedBigInteger('sales_id');
            $table->string('nama_toko', 100);
            $table->string('nama_produk', 100); 
            $table->smallInteger('jumlah')->unsigned()->default(1); 
            $table->decimal('harga', 6, 2); 
            $table->decimal('total', 8, 2); 
            $table->date('tanggal_transaksi'); 
            $table->timestamps();
        
            $table->foreign('sales_id')->references('id')->on('sales')->onDelete('cascade');
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
