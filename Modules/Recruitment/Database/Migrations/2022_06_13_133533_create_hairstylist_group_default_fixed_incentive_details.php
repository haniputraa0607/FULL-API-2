<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistGroupDefaultFixedIncentiveDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_group_default_fixed_incentive_details', function (Blueprint $table) {
            $table->Increments('id_hairstylist_group_default_fixed_incentive_detail');
            $table->integer('id_hairstylist_group_default_fixed_incentive')->nullable()->unsigned();
            $table->string('range')->nullable();
            $table->string('value')->nullable();
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
        Schema::dropIfExists('hairstylist_group_default_fixed_incentive_details');
    }
}
