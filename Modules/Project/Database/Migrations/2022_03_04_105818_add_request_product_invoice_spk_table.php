<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRequestProductInvoiceSpkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_spk', function (Blueprint $table) {
            $table->integer('id_request_product')->nullable()->unsigned()->after('id_branch');

            $table->foreign('id_request_product', 'fk_invoice_request_product')->references('id_request_product')->on('request_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_spk', function (Blueprint $table) {
            $table->dropForeign('fk_invoice_request_product');
            $table->dropIndex('fk_invoice_request_product');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('id_request_product');
        });
    }
}
