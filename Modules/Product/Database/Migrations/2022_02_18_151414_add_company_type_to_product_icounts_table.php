<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCompanyTypeToProductIcountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_icounts', function (Blueprint $table) {
            $table->string('company_type')->default('ima')->index()->after('id_company');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_icounts', function (Blueprint $table) {
            $table->dropColumn('company_type');
        });
    }
}
