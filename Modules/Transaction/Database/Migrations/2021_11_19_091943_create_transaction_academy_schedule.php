<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionAcademySchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_academy_schedules', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_academy_schedule');
            $table->unsignedInteger('id_transaction_academy');
            $table->unsignedInteger('id_user');
            $table->enum('transaction_academy_schedule_status', ['Not Started', 'Attend', 'Absent'])->default('Not Started');
            $table->integer('meeting');
            $table->dateTime('schedule_date');
            $table->smallInteger('change_schedule');
            $table->integer('count_change_schedule');
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
        Schema::dropIfExists('transaction_academy_schedules');
    }
}
