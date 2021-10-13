<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsFitOutTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_fit_out', function (Blueprint $table) {
            $table->increments('id_projects_fit_out');
            $table->integer('id_project')->unsigned();
            $table->foreign('id_project', 'fk_fit_out_project')->references('id_project')->on('projects')->onDelete('restrict');
            $table->string('title',255);
            $table->integer('progres');
            $table->text('note');
            $table->string('attachment',255);
            $table->enum('status',['Process','Success'])->default('Process');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects_fit_out');
    }
}
