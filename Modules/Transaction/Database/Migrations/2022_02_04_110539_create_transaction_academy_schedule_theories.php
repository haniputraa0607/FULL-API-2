<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionAcademyScheduleTheories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_academy_schedule_theories', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_academy_schedule_theory');
            $table->unsignedInteger('id_transaction_academy');
            $table->unsignedInteger('id_transaction_academy_schedule');
            $table->unsignedInteger('id_theory');
            $table->mediumText('theory_title');
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
        Schema::dropIfExists('transaction_academy_schedule_theories');
    }
}
