<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionAcademyInstallment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_academy_installment', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_academy_installment');
            $table->unsignedInteger('id_transaction_academy');
            $table->integer('percent');
            $table->integer('amount');
            $table->date('deadline')->nullable();
            $table->dateTime('completed_installment_at')->nullable();
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
        Schema::dropIfExists('transaction_academy_installment');
    }
}
