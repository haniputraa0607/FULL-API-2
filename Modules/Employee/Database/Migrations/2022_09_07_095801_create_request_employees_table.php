<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_employees', function (Blueprint $table) {
            $table->increments('id_request_employee');
            $table->integer('id_outlet')->unsigned();
            $table->bigInteger('id_department')->unsigned();
            $table->integer('number_of_request');
            $table->enum('status', ['Request','Approved','Rejected','Done Approved'])->default('Request');
            $table->integer('id_user')->unsigned()->nullable();
            $table->text('id_employee')->nullable();
            $table->text('notes_om')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('id_outlet', 'fk_request_employee_outlet')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('id_department', 'fk_request_employee_department')->references('id_department')->on('departments')->onDelete('cascade');
            $table->foreign('id_user', 'fk_request_employee_user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_employees');
    }
}
