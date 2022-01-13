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
           $table->string('nama_kontraktor')->nullable();
            $table->string('cp_kontraktor')->nullable();
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
            $table->dropColumn('nama_kontraktor')->nullable();
            $table->dropColumn('cp_kontraktor')->nullable();
        });
    }
}
