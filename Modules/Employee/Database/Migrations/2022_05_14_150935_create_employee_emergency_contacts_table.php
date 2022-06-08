<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeEmergencyContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('employee_emergency_contacts', function (Blueprint $table) {
            $table->Increments('id_employee_emergency_contact');
            $table->unsignedInteger('id_user');
            $table->string('name_emergency_contact')->nullable();
            $table->string('relation_emergency_contact')->nullable();
            $table->string('phone_emergency_contact')->nullable();
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
        Schema::dropIfExists('employee_emergency_contacts');
    }
}
