<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_items', function (Blueprint $table) {
            $table->bigInteger('harga_beli')->nullable()->default(0);
            $table->string('kategori')->nullable(); // snapshot kategori barang: Unit AC/Sparepart/Jasa
        });

        Schema::table('transaksis', function (Blueprint $table) {
            $table->boolean('sudah_diproses_pelunasan')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_items', function (Blueprint $table) {
            $table->dropColumn(['harga_beli', 'kategori']);
        });
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropColumn('sudah_diproses_pelunasan');
        });
    }
};