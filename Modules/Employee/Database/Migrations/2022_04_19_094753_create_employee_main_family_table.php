<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeMainFamilyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_main_families', function (Blueprint $table) {
            $table->Increments('id_employee_main_family');
            $table->integer('id_user')->unsigned();
            $table->string('family_members')->nullable();
            $table->string('name_family')->nullable();
            $table->enum('gender_family',['Male','Female'])->nullable();
            $table->string('birthplace_family')->nullable();
            $table->date('birthday_family')->nullable();
            $table->string('education_family')->nullable();
            $table->string('job_family')->nullable();
            $table->foreign('id_user', 'fk_user_employee_main_families')->references('id')->on('users')->onDelete('restrict');
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
        Schema::dropIfExists('employee_main_families');
    }
}
