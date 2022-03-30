<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUnitIcountConversionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('unit_icount_conversions', function (Blueprint $table) {
            $table->dropForeign('fk_unit_conversion_product_icount');
            $table->dropIndex('fk_unit_conversion_product_icount');
            $table->dropColumn('id_product_icount');

            $table->integer('id_unit_icount')->after('id_unit_icount_conversion')->unsigned();
            $table->foreign('id_unit_icount', 'fk_conversion_master')->references('id_unit_icount')->on('unit_icounts')->onDelete('cascade');

            $table->dropColumn('qty');
            $table->dropColumn('unit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unit_icount_conversions', function (Blueprint $table) {
            $table->dropForeign('fk_conversion_master');
            $table->dropIndex('fk_conversion_master');
            $table->dropColumn('id_unit_icount');

            $table->integer('id_product_icount')->after('id_unit_icount_conversion')->unsigned();
            $table->foreign('id_product_icount', 'fk_unit_conversion_product_icount')->references('id_product_icount')->on('product_icounts')->onDelete('cascade');

            $table->integer('qty')->default('1')->after('id_product_icount');
            $table->string('unit')->nullable()->after('qty');
        });
    }
}
