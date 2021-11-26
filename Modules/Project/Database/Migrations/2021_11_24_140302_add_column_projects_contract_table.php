<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnProjectsContractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects_contract', function (Blueprint $table) {
            $table->string('nomor_loi',255)->nullable();
            $table->date('tanggal_loi')->nullable();
            $table->date('tanggal_serah_terima')->nullable();
            $table->date('tanggal_buka_loi')->nullable();
            $table->string('nama_pic',255)->nullable();
            $table->string('kontak_pic',255)->nullable();
            $table->string('lokasi_pic',255)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects_contract', function (Blueprint $table) {
            $table->dropColumn('nomor_loi',255)->nullable();
            $table->dropColumn('tanggal_loi')->nullable();
            $table->dropColumn('tanggal_serah_terima')->nullable();
            $table->dropColumn('tanggal_buka_loi')->nullable();
            $table->dropColumn('nama_pic',255)->nullable();
            $table->dropColumn('kontak_pic',255)->nullable();
            $table->dropColumn('lokasi_pic',255)->nullable();
        });
    }
}
