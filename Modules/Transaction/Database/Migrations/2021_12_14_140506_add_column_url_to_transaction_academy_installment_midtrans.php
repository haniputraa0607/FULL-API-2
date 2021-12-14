<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnUrlToTransactionAcademyInstallmentMidtrans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_academy_installment_payment_midtrans', function (Blueprint $table) {
            $table->mediumText('redirect_url')->nullable('status_message');
            $table->mediumText('token')->nullable()->after('status_message');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_academy_installment_payment_midtrans', function (Blueprint $table) {
            $table->dropColumn('token');
            $table->dropColumn('redirect_url');
        });
    }
}
