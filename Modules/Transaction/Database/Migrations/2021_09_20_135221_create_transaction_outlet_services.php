<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionOutletServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_outlet_services', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_outlet_services');
            $table->unsignedInteger('id_transaction');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_domicile')->nullable();
            $table->date('customer_birtdate')->nullable();
            $table->enum('customer_gender', ['Male', 'Female'])->nullable();
            $table->string('pickup_by')->nullable();
            $table->dateTime('pickup_at')->nullable();
            $table->dateTime('completed_at')->nullable();
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
        Schema::dropIfExists('transaction_outlet_services');
    }
}
