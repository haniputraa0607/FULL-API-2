<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFkIdUserToFraudDay extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fraud_detection_log_transaction_day', function (Blueprint $table) {
            $table->foreign('id_user', 'fk_fraud_detection_log_transaction_day_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fraud_detection_log_transaction_day', function (Blueprint $table) {
            $table->dropForeign('fk_fraud_detection_log_transaction_day_users');
        });
    }
}
