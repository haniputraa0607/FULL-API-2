<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnTanggalSerahTerimaProjectsHandoverTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects_handover', function (Blueprint $table) {
            $table->date('tanggal_serah_terima')->nullable(); 
            $table->datetime('soft_opening')->nullable(); 
            $table->datetime('grand_opening')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects_handover', function (Blueprint $table) {
            $table->dropColumn('tanggal_serah_terima')->nullable();
            $table->dropColumn('soft_opening')->nullable();
            $table->dropColumn('grand_opening')->nullable();
        });
    }
}
