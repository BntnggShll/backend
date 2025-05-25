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
        Schema::create('payments', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->Integer('order_id');
            $table->decimal('jumlah', 8, 2);
            $table->enum('metode_pembayaran', ['transfer', 'cod', 'ewallet']);
            $table->enum('status_pembayaran', ['menunggu', 'selesai', 'gagal'])->default('menunggu');
            $table->date('tanggal_transaksi');
            $table->timestamps();
        
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
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
