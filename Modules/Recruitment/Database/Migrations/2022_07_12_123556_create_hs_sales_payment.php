<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsSalesPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_sales_payments', function (Blueprint $table) {
            $table->Increments('id_hairstylist_sales_payment');
            $table->string('BusinessPartnerID')->nullable();
            $table->integer('amount')->nullable();
            $table->string('SalesInvoiceID')->nullable();
            $table->enum('status',['Pending','Success','Reject'])->default('Pending');
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
        Schema::dropIfExists('hairstylist_sales_payments');
    }
}
