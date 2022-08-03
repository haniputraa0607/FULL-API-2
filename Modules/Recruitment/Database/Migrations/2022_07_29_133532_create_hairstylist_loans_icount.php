<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistLoansIcount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_loan_return_icounts', function (Blueprint $table) {
            $table->Increments('id_hairstylist_loan_return_icount');
            $table->unsignedInteger('id_hairstylist_loan_return');
            $table->foreign('id_hairstylist_loan_return', 'fk_return_icount_id_hairstylist_loan')
                    ->references('id_hairstylist_loan')->on('hairstylist_loan_returns')->references('id_hairstylist_loan_return')->onDelete('cascade');
            $table->string('SalesPaymentID')->nullable();
            $table->string('SalesInvoiceID')->nullable();
            $table->string('BusinessPartnerID')->nullable();
            $table->string('CompanyID')->nullable();
            $table->string('BranchID')->nullable();
            $table->string('VoucherNo')->nullable();
            $table->text('value_detail')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hairstylist_loan_return_icounts');
    }
}
