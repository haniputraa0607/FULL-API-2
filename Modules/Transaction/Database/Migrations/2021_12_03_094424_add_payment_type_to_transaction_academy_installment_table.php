<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentTypeToTransactionAcademyInstallmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_academy_installment', function (Blueprint $table) {
            $table->enum('installment_payment_type', ['Midtrans', 'Xendit'])->after('amount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_academy_installment', function (Blueprint $table) {
            $table->dropColumn('installment_payment_type');
        });
    }
}
