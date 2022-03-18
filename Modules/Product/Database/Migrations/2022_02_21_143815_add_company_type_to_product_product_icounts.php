<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCompanyTypeToProductProductIcounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_product_icounts', function (Blueprint $table) {
            $table->enum('company_type',['ima','ims'])->default('ima')->after('id_product_icount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_product_icounts', function (Blueprint $table) {
            $table->dropColumn('company_type');
        });
    }
}
