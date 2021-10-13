<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsDesainTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_desain', function (Blueprint $table) {
            $table->increments('id_projects_desain');
            $table->integer('id_project')->unsigned();
            $table->foreign('id_project', 'fk_desain_project')->references('id_project')->on('projects')->onDelete('restrict');
            $table->integer('desain');
            $table->text('note');
            $table->string('attachment',255);
            $table->enum('status',['Revisi','Success','Reject'])->default('Revisi');
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
        Schema::dropIfExists('projects_desain');
    }
}
