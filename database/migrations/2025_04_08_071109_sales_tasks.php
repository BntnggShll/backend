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
            $table->id();
            $table->unsignedBigInteger('sales_id');
            $table->enum('jenis_tugas', ['pengantaran', 'promosi']);
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->unsignedBigInteger('shipment_id')->nullable();
            $table->timestamps();
        
            $table->foreign('sales_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('set null');
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
