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
    Schema::table('paket_barangs', function (Blueprint $table) {
        $table->bigInteger('harga_paket')->nullable(); // harga jual gabungan, kalau null = jumlah normal (tanpa diskon)
    });
}

public function down(): void
{
    Schema::table('paket_barangs', function (Blueprint $table) {
        $table->dropColumn('harga_paket');
    });
}
};
