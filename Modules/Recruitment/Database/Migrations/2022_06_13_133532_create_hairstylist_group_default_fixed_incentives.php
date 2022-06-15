<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistGroupDefaultFixedIncentives extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_group_default_fixed_incentives', function (Blueprint $table) {
            $table->Increments('id_hairstylist_group_default_fixed_incentive');
            $table->string('name_fixed_incentive')->nullable();
            $table->enum('type',['Type 1','Type 2'])->nullable();
            $table->enum('formula',['outlet_age','years_of_service','monthly'])->nullable();
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
        Schema::dropIfExists('hairstylist_group_default_fixed_incentives');
    }
}
