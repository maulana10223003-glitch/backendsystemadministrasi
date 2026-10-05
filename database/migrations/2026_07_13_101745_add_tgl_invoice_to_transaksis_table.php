<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->date('tgl_invoice')->nullable();
            $table->dropForeign(['teknisi_id']);
            $table->dropColumn('teknisi_id');
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropColumn('tgl_invoice');
            $table->string('teknisi_id')->nullable();
        });
    }
};