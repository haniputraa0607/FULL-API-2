<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAmountSalesPayment extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    } 
    public function up()
    {
      Schema::table('hairstylist_sales_payments', function (Blueprint $table) {
            $table->decimal('amount',25,4)->change()->nullable();
            $table->enum('type', ['IMS','IMA'])->default('ims');
        });
      Schema::table('hairstylist_loans', function (Blueprint $table) {
            $table->decimal('amount',25,4)->change()->nullable();
        });
      Schema::table('hairstylist_loan_returns', function (Blueprint $table) {
            $table->decimal('amount_return',25,4)->change()->nullable();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('hairstylist_sales_payments', function (Blueprint $table) {
            $table->decimal('amount',25,4)->change()->nullable();
        });
         Schema::table('hairstylist_loans', function (Blueprint $table) {
            $table->decimal('amount',25,4)->change()->nullable();
        });
      Schema::table('hairstylist_loan_returns', function (Blueprint $table) {
            $table->decimal('amount_return',25,4)->change()->nullable();
        });
    }
}
