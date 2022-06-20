<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDatePayHairstylistLoan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_loan_returns', function (Blueprint $table) {
            $table->dateTime('date_pay')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('hairstylist_loan_returns', function (Blueprint $table) {
             $table->dropColumn('date_pay')->nullable();
        });
    }
}
