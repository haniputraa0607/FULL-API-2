<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBudgetCodeToRequestProductDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_product_details', function (Blueprint $table) {
            $table->enum('budget_code',["Invoice","Beban","Assets"])->default('Invoice')->after('filter');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_product_details', function (Blueprint $table) {
            $table->dropColumn('budget_code');
        });
    }
}
