<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_sales_payments', function (Blueprint $table) {
            $table->Increments('id_employee_sales_payment');
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
        Schema::dropIfExists('employee_sales_payment');
    }
}
