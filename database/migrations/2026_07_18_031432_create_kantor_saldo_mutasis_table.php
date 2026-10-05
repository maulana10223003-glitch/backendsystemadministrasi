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
    Schema::create('kantor_saldo_mutasi', function (Blueprint $table) {
        $table->id();
        $table->string('transaksi_id')->nullable();
        $table->enum('jenis', ['Kredit', 'Debit']);
        $table->string('kategori'); // Laba Barang, Bagi Hasil Jasa, Tabungan 5%, Pengeluaran Operasional
        $table->bigInteger('jumlah');
        $table->string('keterangan')->nullable();
        $table->timestamps();

        $table->foreign('transaksi_id')->references('id')->on('transaksis')->onDelete('set null');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kantor_saldo_mutasis');
    }
};
