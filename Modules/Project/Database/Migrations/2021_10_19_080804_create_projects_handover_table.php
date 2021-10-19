<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsHandoverTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_handover', function (Blueprint $table) {
           $table->increments('id_projects_handover');
            $table->integer('id_project')->unsigned();
            $table->foreign('id_project', 'fk_handover_project')->references('id_project')->on('projects')->onDelete('restrict');
            $table->string('title',255);
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
        Schema::dropIfExists('projects_handover');
    }
}
