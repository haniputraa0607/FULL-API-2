<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->increments('id_project');
            $table->string('name');
            $table->integer('id_partner')->unsigned();
            $table->integer('id_location')->unsigned();
            $table->dateTime('start_project')->nullable();
            $table->enum('status',['Process','Success','Reject'])->default('Process');
            $table->enum('progres',['Survey Location','Desain Location','Contract','Fit Out','Success'])->default('Survey Location');
            $table->string('note')->nullable();
            $table->timestamps();
            $table->foreign('id_partner', 'fk_project_partner')->references('id_partner')->on('partners')->onDelete('restrict');
            $table->foreign('id_location', 'fk_project_location')->references('id_location')->on('locations')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project');
    }
}
