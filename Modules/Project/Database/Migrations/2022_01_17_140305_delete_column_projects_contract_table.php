<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteColumnProjectsContractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects_contract', function (Blueprint $table) {
           $table->dropColumn('nomor_loi')->nullable();
           $table->dropColumn('tanggal_loi')->nullable();
           $table->dropColumn('nomor_spk')->nullable();
           $table->dropColumn('tanggal_spk')->nullable();
           $table->dropColumn('lampiran')->nullable();
           $table->dropColumn('tanggal_buka_loi')->nullable();
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
            $table->string('nomor_loi')->nullable();
            $table->string('tanggal_loi')->nullable();
            $table->string('nomor_spk')->nullable();
            $table->string('tanggal_spk')->nullable();
            $table->string('lampiran')->nullable();
            $table->string('tanggal_buka_loi')->nullable();
        });
    }
}
