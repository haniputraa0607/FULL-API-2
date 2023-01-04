<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHolidayDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hs_holidays', function (Blueprint $table) {
            $table->Increments('id_hs_holiday');
            $table->date('holiday_date')->nullable();
            $table->string('holiday_name')->nullable();
            $table->integer('month')->nullable();
            $table->string('year')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hs_holidays');
    }
}
