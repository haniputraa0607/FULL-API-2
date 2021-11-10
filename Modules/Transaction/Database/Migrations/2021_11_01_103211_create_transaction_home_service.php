<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionHomeService extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_home_services', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_home_service');
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_user_address');
            $table->unsignedInteger('id_user_hair_stylist')->nullable();
            $table->enum('status', ['Finding Hair Stylist', 'Get Hair Stylist', 'On The Way', 'Arrived', 'Start Service', 'Completed', 'Cancelled']);
            $table->date('schedule_date');
            $table->time('schedule_time');
            $table->enum('preference_hair_stylist', ['All', 'Female', 'Male', 'Favorite'])->nullable();
            $table->string('destination_name');
            $table->string('destination_phone');
            $table->text('destination_address');
            $table->string('destination_short_address')->nullable();
            $table->string('destination_address_name')->nullable();
            $table->mediumText('destination_note')->nullable();
            $table->string('destination_latitude');
            $table->string('destination_longitude');
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
        Schema::dropIfExists('transaction_home_services');
    }
}
