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
            $table->unsignedBigInteger('sales_id'); // relasi ke tabel sales
            $table->string('store_name', 100); // nama toko tempat promosi/penjualan
            $table->string('product_name', 100); // nama produk yang dijual
            $table->smallInteger('quantity')->unsigned()->default(1); // jumlah produk
            $table->decimal('price', 6, 2); // harga per produk
            $table->decimal('total', 8, 2); // total = price * quantity
            $table->date('transaction_date'); // tanggal transaksi
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
