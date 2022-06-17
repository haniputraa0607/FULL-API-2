<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUseDepartmenBudgetToRequestProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_products', function (Blueprint $table) {
            $table->tinyInteger('use_department_budget')->default(0)->after('id_purchase_request');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_products', function (Blueprint $table) {
            $table->dropColumn('use_department_budget');
        });
    }
}
