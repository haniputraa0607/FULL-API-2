<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionAcademyScheduleDayOff extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_academy_schedule_day_off', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_academy_schedule_day_off');
            $table->unsignedInteger('id_transaction_academy');
            $table->unsignedInteger('id_transaction_academy_schedule');
            $table->dateTime('schedule_date_old');
            $table->dateTime('schedule_date_new');
            $table->mediumText('description')->nullable();
            $table->unsignedInteger('approve_by')->nullable();
            $table->dateTime('approve_date')->nullable();
            $table->unsignedInteger('reject_by')->nullable();
            $table->dateTime('reject_date')->nullable();
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
        Schema::dropIfExists('transaction_academy_schedule_day_off');
    }
}
