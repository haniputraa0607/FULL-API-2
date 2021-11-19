<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnRespon2IcountToPartnerAndLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('id_sales_invoice')->after('id_sales_order_detail')->nullable();
            $table->string('id_sales_invoice_detail')->after('id_sales_invoice')->nullable();
            $table->string('id_delivery_order_detail')->after('id_sales_invoice_detail')->nullable();
        });
        Schema::table('steps_logs', function (Blueprint $table) {
            $table->dropForeign('fk_step_partner');
            $table->dropIndex('fk_step_partner');
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
            $table->dropColumn('id_sales_invoice');
            $table->dropColumn('id_sales_invoice_detail');
            $table->dropColumn('id_delivery_order_detail');
        });
        Schema::table('steps_logs', function (Blueprint $table) {
            $table->foreign('id_partner', 'fk_step_partner')->references('id_partner')->on('partners')->onDelete('restrict');
        });
    }
}
