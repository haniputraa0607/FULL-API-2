<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionAcademyInstallmentPaymentXenditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_academy_installment_payment_xendits', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_academy_installment_payment_xendit');
            $table->unsignedBigInteger('id_transaction_academy');
            $table->unsignedBigInteger('id_transaction_academy_installment');
            $table->string('order_id')->nullable();
            $table->string('xendit_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('business_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('type')->nullable();
            $table->string('amount')->nullable();
            $table->string('expiration_date')->nullable();
            $table->string('failure_code')->nullable();
            $table->string('status')->nullable();
            $table->text('checkout_url')->nullable();
            $table->timestamps();

            $table->foreign('id_transaction_academy', 'fk_ita_taipx')->references('id_transaction_academy')->on('transaction_academy')->onDelete('cascade');
            $table->foreign('id_transaction_academy_installment', 'fk_itai_taipx')->references('id_transaction_academy_installment')->on('transaction_academy_installment')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_academy_installment_payment_xendits');
    }
}
