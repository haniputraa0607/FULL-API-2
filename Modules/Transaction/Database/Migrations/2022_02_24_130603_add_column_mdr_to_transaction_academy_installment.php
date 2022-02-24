<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnMdrToTransactionAcademyInstallment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_academy_installment', function (Blueprint $table) {
            $table->decimal('mdr_payment_installment', 30, 4)->default(0)->after('void_date');
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
            $table->dropColumn('mdr_payment_installment');
        });
    }
}
