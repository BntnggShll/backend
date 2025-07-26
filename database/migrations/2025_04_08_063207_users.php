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
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('name',100);
            $table->enum('role', ['admin','customer', 'sales', 'reseller'])->default('admin');
            $table->text('alamat')->nullable();
            $table->string('email',150)->unique();
            $table->string('password');
            $table->string('image')->nullable();
            $table->string('no_telp',15)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
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
