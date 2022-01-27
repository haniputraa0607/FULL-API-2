<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletChangeLocationConfirmationLetterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_change_location_confirmation_letter', function (Blueprint $table) {
            $table->bigIncrements('id_outlet_change_location_confirmation_letter');
            $table->integer('id_outlet_change_location')->nullable();
            $table->string('no_letter',255)->nullable();
            $table->string('location',255)->nullable();
            $table->date('date')->nullable();
            $table->string('attachment',255)->nullable();
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
        Schema::dropIfExists('outlet_change_location_confirmation_letter');
    }
}
