<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnProjectsDesainTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects_desain', function (Blueprint $table) {
            $table->string('nama_designer',255)->nullable();
            $table->string('cp_designer',255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects_desain', function (Blueprint $table) {
            $table->dropColumn('nama_designer',255)->nullable();
            $table->dropColumn('cp_designer')->nullable();
        });
    }
}
