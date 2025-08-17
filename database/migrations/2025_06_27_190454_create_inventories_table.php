// ..._create_inventories_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('product_unit_id');
            $table->integer('quantity');
            $table->enum('status',['gudang','sales'])->default('gudang');
            $table->timestamps();

            $table->foreign('product_unit_id')->references('id')->on('product_units')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};