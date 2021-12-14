<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionAcademyInstallmentUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_academy_installment_updates', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_academy_installment_update');
            $table->unsignedInteger('id_transaction_academy_installment');
            $table->string('installment_receipt_number_old');
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
        Schema::dropIfExists('transaction_academy_installment_updates');
    }
}
