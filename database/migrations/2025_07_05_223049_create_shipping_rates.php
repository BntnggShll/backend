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
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->primary();
            $table->string('nama_daerah');
            $table->unsignedInteger('cost')->default(0);    
            $table->string('nama_penerima', 150)->after('user_id');
            $table->string('nomor_telp', 15)->after('nama_penerima');
            $table->text('catatan')->nullable()->after('nomor_telp');
            $table->text('alamat_pengantaran')->after('catatan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
