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
    Schema::create('transaksis', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('pelanggan');
        $table->text('alamat');
        $table->string('telepon');
        $table->date('tgl_pengerjaan');
        $table->date('tgl_bayar')->nullable();
        $table->string('teknisi_id');
        $table->enum('metode_bayar', ['Tunai', 'Transfer Bank', 'QRIS']);
        $table->enum('status_pengerjaan', ['Menunggu', 'Dikerjakan', 'Selesai'])->default('Menunggu');
        $table->enum('status_pembayaran', ['Belum Bayar', 'DP', 'Lunas'])->default('Belum Bayar');
        $table->timestamps();

        $table->foreign('teknisi_id')->references('id')->on('teknisis');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
