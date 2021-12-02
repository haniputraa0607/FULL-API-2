<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentStatusToTransactionInstallment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_academy_installment', function (Blueprint $table) {
            $table->dateTime('void_date')->nullable()->after('completed_installment_at');
            $table->integer('installment_step')->nullable()->after('installment_receipt_number');
            $table->enum('paid_status', ['Pending', 'Paid', 'Completed', 'Cancelled'])->nullable()->after('deadline');
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
            $table->dropColumn('paid_status');
            $table->dropColumn('installment_step');
            $table->dropColumn('void_date');
        });
    }
}
