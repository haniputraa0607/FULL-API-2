<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReciptNumberToTransactionAcademyInstallment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_academy_installment', function (Blueprint $table) {
            $table->string('installment_receipt_number', 230)->nullable()->after('id_transaction_academy');
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
            $table->dropColumn('installment_receipt_number');
        });
    }
}
