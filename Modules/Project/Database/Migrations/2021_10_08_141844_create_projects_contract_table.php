<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsContractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_contract', function (Blueprint $table) {
            $table->increments('id_projects_contract');
            $table->integer('id_project')->unsigned();
            $table->foreign('id_project', 'fk_contract_project')->references('id_project')->on('projects')->onDelete('restrict');
            $table->string('first_party',255);
            $table->string('second_party',255);
            $table->integer('nominal');
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
        Schema::dropIfExists('projects_contract');
    }
}
