<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameColumnNameIdTransferToTransactionPaymentCash extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payment_cash', function(Blueprint $table)
        {
            $table->renameColumn('id_hairstylist_transfer_payment', 'id_outlet_cash');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_payment_cash', function(Blueprint $table)
        {
            $table->renameColumn('id_outlet_cash', 'id_hairstylist_transfer_payment');
        });
    }
}
