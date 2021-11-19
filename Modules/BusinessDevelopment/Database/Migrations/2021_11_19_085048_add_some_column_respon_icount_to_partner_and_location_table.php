<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnResponIcountToPartnerAndLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('id_business_partner')->after('id_partner')->nullable();
            $table->string('id_sales_order')->after('id_salesman')->nullable();
            $table->string('id_sales_order_detail')->after('id_sales_order')->nullable();
            $table->string('voucher_no')->after('id_sales_deposit')->nullable();
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->string('id_branch')->after('id_location')->nullable();
            $table->text('value_detail')->after('total_payment')->nullable();
        });
        Schema::table('outlets', function (Blueprint $table) {
            $table->string('id_branch')->after('id_moka_account_business')->nullable();
            $table->string('branch_code')->after('id_branch')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('id_business_partner');
            $table->dropColumn('id_sales_order');
            $table->dropColumn('id_sales_order_detail');
            $table->dropColumn('voucher_no');
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('id_branch');
            $table->dropColumn('value_detail');
        });
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn('id_branch');
            $table->dropColumn('branch_code');
        });
    }
}
