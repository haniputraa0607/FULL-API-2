<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdTransferPaymentToTransactionPaymentCash extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_cash', function (Blueprint $table) {
            $table->unsignedInteger('id_hairstylist_transfer_payment')->nullable()->after('cash_received_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_payment_cash', function (Blueprint $table) {
            $table->dropColumn('id_hairstylist_transfer_payment');
        });
    }
}
