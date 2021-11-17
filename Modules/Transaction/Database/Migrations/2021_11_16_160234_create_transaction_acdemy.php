<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionAcdemy extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_academy', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_academy');
            $table->unsignedInteger('id_transaction');
            $table->enum('payment_method', ['one_time_payment', 'installment']);
            $table->integer('total_installment')->nullable();
            $table->integer('amount_completed')->default(0);
            $table->integer('amount_not_completed')->default(0);
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
        Schema::dropIfExists('transaction_academy');
    }
}
