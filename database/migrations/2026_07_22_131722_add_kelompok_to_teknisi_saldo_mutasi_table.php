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
    Schema::table('teknisi_saldo_mutasi', function (Blueprint $table) {
        $table->enum('kelompok', ['Tabungan', 'Gaji'])->after('kategori')->default('Tabungan');
    });
}

public function down(): void
{
    Schema::table('teknisi_saldo_mutasi', function (Blueprint $table) {
        $table->dropColumn('kelompok');
    });
}
};
