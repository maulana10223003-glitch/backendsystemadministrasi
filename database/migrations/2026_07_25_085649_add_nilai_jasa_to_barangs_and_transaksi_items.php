<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->bigInteger('nilai_jasa')->nullable()->default(0);
        });
        Schema::table('transaksi_items', function (Blueprint $table) {
            $table->bigInteger('nilai_jasa')->nullable()->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->dropColumn('nilai_jasa');
        });
        Schema::table('transaksi_items', function (Blueprint $table) {
            $table->dropColumn('nilai_jasa');
        });
    }
};