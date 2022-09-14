<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeCustomLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_custom_links', function (Blueprint $table) {
            $table->Increments('id_employee_custom_link');
            $table->unsignedInteger('id_employee');
            $table->string('title');
            $table->text('link');
            $table->timestamps();

            $table->foreign('id_employee', 'fk_employee_links')->references('id_employee')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_custom_links');
    }
}
