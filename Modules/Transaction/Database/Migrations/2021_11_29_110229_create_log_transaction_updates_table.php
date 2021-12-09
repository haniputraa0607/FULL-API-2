<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogTransactionUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	Schema::connection('mysql2')->create('log_transaction_updates', function (Blueprint $table) {
            $table->bigIncrements('id_log_transaction_update');
            $table->integer('id_user')->unsigned()->index('fk_transaction_updates_users');
            $table->integer('id_transaction')->unsigned()->index('fk_transaction_updates_transaction');
            $table->enum('transaction_from', ['outlet-service', 'home-service', 'shop', 'academy']);
            $table->text('old_data')->nullable();
            $table->text('new_data')->nullable();
            $table->text('note')->nullable();
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
        Schema::connection('mysql2')->dropIfExists('log_transaction_updates');
    }
}
