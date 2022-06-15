<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistGroupFixedIncentive extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_group_fixed_incentives', function (Blueprint $table) {
            $table->Increments('id_hairstylist_group_fixed_incentive');
            $table->integer('id_hairstylist_group')->unsigned();
            $table->integer('id_hairstylist_group_default_fixed_incentive_detail')->unsigned();
            $table->foreign('id_hairstylist_group', 'fk_hairstylist_group_fixed_incentive')->references('id_hairstylist_group')->on('hairstylist_groups')->onDelete('restrict');
            $table->foreign('id_hairstylist_group_default_fixed_incentive_detail', 'fk_id_hairstylist_group_default_fixed_incentive_detail')->references('id_hairstylist_group_default_fixed_incentive_detail')->on('hairstylist_group_default_fixed_incentive_details')->onDelete('restrict');
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
        Schema::dropIfExists('hairstylist_group_fixed_incentive');
    }
}
