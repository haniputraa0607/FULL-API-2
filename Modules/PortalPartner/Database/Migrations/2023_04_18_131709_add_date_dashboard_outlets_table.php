<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDateDashboardOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_portal_reports', function (Blueprint $table) {
            $table->increments('id_outlet_portal_report');
            $table->integer('id_outlet')->nullable();
            $table->date('date')->nullable();
            $table->integer('jumlah')->nullable();
            $table->integer('revenue')->nullable();
            $table->integer('grand_total')->nullable();
            $table->integer('diskon')->nullable();
            $table->integer('tax')->nullable();
            $table->integer('mdr')->nullable();
            $table->integer('net_sales')->nullable();
            $table->integer('net_sales_mdr')->nullable();
            $table->integer('count_hs')->nullable();
            $table->integer('refund_product')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_portal_reports');
    }
}
