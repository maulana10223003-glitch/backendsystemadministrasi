<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paket_komponen', function (Blueprint $table) {
            $table->id();
            $table->string('paket_id');
            $table->string('komponen_id');
            $table->integer('qty');
            $table->timestamps();

            $table->foreign('paket_id')->references('id')->on('barangs')->onDelete('cascade');
            $table->foreign('komponen_id')->references('id')->on('barangs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paket_komponen');
    }
};