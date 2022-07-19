<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeCategoryHairstylistLoan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_loans', function (Blueprint $table) {
            $table->dropColumn('id_hairstylist_category_loan');
        });
        Schema::table('hairstylist_loans', function (Blueprint $table) {
            $table->unsignedInteger('id_hairstylist_category_loan');
            $table->foreign('id_hairstylist_category_loan')->on('hairstylist_category_loans')->references('id_hairstylist_category_loan')->onDelete('cascade');
            
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
            $table->string('id_hairstylist_category_loan');
        });
    }
}
