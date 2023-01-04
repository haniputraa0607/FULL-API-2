<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHairstylistIncomeValueDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_incomes', function (Blueprint $table) {
            $table->text('value_detail')->nullable();
        });
        Schema::table('hairstylist_income_details', function (Blueprint $table) {
            $table->enum('type',['Incentive','Salary Cut'])->nullable();
            $table->string('name_income')->nullable();
            $table->text('value_detail')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_incomes', function (Blueprint $table) {
            $table->dropColumn('value_detail');
        });
        Schema::table('hairstylist_income_details', function (Blueprint $table) {
            $table->dropColumn('type',['Incentive','Salary Cut'])->nullable();
            $table->dropColumn('name_income')->nullable();
            $table->dropColumn('value_detail')->nullable();
        });
    }
}
