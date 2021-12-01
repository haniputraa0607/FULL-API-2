<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnProjectsContractNomorspkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects_contract', function (Blueprint $table) {
            $table->string('nomor_spk',255)->nullable();
            $table->date('tanggal_spk')->nullable();
            $table->string('lampiran',255)->nullable();
            $table->dropColumn('tanggal_serah_terima')->nullable();
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
           $table->dropColumn('nomor_spk')->nullable();
           $table->dropColumn('tanggal_spk')->nullable();
           $table->dropColumn('lampiran')->nullable();
            $table->string('tanggal_serah_terima')->nullable();
        });
    }
}
