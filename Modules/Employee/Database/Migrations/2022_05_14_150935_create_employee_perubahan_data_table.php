<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeePerubahanDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('employee_perubahan_datas', function (Blueprint $table) {
            $table->Increments('id_employee_perubahan_data');
            $table->unsignedInteger('id_user');
            $table->string('key')->nullable();
            $table->string('name')->nullable();
            $table->string('change_data')->nullable();
            $table->string('notes')->nullable();
            $table->enum('status',['Pending',"Success","Reject"])->default('Pending');
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
        Schema::dropIfExists('employee_perubahan_datas');
    }
}
