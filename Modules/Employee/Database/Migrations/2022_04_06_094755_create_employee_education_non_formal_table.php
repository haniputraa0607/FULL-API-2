<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeEducationNonFormalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_education_non_formal', function (Blueprint $table) {
            $table->Increments('id_employee_education_non_formal');
            $table->integer('id_user')->unsigned();
            $table->string('course_type')->nullable();
            $table->year('year_education_non_formal')->nullable();
            $table->string('long_term')->nullable();
            $table->boolean('certificate')->default(0);
            $table->string('financed_by')->nullable();
            $table->foreign('id_user', 'fk_user_employee_education_non_formal')->references('id')->on('users')->onDelete('restrict');
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
        Schema::dropIfExists('employee_education_non_formal');
    }
}
