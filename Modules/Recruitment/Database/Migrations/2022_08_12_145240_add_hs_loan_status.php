<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHsLoanStatus extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    } 
    public function up()
    {
       Schema::table('hairstylist_loans', function (Blueprint $table) {
            $table->dropColumn('status_loan')->default();
        });
       Schema::table('hairstylist_loans', function (Blueprint $table) {
            $table->enum('status_loan',['Success','Reject'])->default('Success');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_loans', function (Blueprint $table) {
             $table->enum('status_loan',['Success','Reject'])->default('Success');
        });
    }
}
