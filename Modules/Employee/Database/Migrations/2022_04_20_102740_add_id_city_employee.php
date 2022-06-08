<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdCityEmployee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->Increments('id_employee');
            $table->integer('id_user')->unsigned();
            $table->string('nickname')->nullable();
            $table->string('country')->nullable();
            $table->string('birthplace')->nullable();
            $table->string('religion')->nullable();
            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('age')->nullable();
            $table->string('place_of_origin')->nullable();
            $table->string('job_now')->nullable();
            $table->string('companies')->nullable();
            $table->string('blood_type')->nullable();
            $table->string('card_number')->nullable();
            $table->string('address_ktp')->nullable();
            $table->integer('id_city_ktp')->unsigned()->nullable();
            $table->string('postcode_ktp')->nullable();
            $table->string('address_domicile')->nullable();
            $table->integer('id_city_domicile')->unsigned()->nullable();
            $table->string('postcode_domicile')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('status_address_domicile')->nullable();
            $table->enum('marital_status',['Single', 'Menikah', 'Janda', 'Duda'])->nullable();
            $table->date('married_date')->nullable();
            $table->string('applied_position')->nullable();
            $table->string('other_position')->nullable();
            $table->string('vacancy_information')->nullable();
            $table->boolean('relatives')->nullable();
            $table->string('relative_name')->nullable();
            $table->string('relative_position')->nullable();
            $table->enum('status',['candidate','active','inactive','rejected'])->default('candidate');
            $table->string('status_step')->nullable();
            $table->enum('status_approved',['Submitted','Interview','Psikotest','HRGA','Contract','Approved','Success'])->nullable();
            $table->foreign('id_user', 'fk_employee_user')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('id_city_ktp', 'fk_employee_city_ktp')->references('id_city')->on('cities')->onDelete('restrict');
            $table->foreign('id_city_domicile', 'fk_employee_city_domicile')->references('id_city')->on('cities')->onDelete('restrict');
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
        Schema::dropIfExists('employees');
    }
}
