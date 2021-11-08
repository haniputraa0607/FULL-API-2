<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionHomeServiceTemporaryHs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_home_service_hairstylist_reject', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_home_service_hairstylist_reject');
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_user_hair_stylist');
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
        Schema::dropIfExists('transaction_home_service_hairstylist_reject');
    }
}
