<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeEducationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_educations', function (Blueprint $table) {
            $table->Increments('id_employee_education');
            $table->integer('id_user')->unsigned();
            $table->enum('educational_level',['SD','SMP','SMA',"Diploma",'Sarjana',"Pascasarjana","Doktoral"])->nullable();
            $table->string('name_school')->nullable();
            $table->year('year_education')->nullable();
            $table->string('study_program')->nullable();
            $table->integer('id_city_school')->unsigned();
            $table->foreign('id_user', 'fk_user_employee_educations')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('id_city_school', 'fk_employee_city_school')->references('id_city')->on('cities')->onDelete('restrict');
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
        Schema::dropIfExists('employee_educations');
    }
}
