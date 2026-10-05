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
    Schema::create('transaksi_items', function (Blueprint $table) {
        $table->id();
        $table->string('transaksi_id');
        $table->string('barang_id');
        $table->string('nama');
        $table->integer('qty');
        $table->bigInteger('harga_jual');
        $table->timestamps();

        $table->foreign('transaksi_id')->references('id')->on('transaksis')->onDelete('cascade');
        $table->foreign('barang_id')->references('id')->on('barangs');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_items');
    }
};
