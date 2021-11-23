<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceBapTable extends Migration
{
    public function up()
    {
        Schema::create('invoice_bap', function (Blueprint $table) {
            $table->increments('id_invoice_bap');
            $table->integer('id_project')->unsigned();
            $table->foreign('id_project', 'fk_project_invoice_bap')->references('id_project')->on('projects')->onDelete('restrict');
            $table->string('id_sales_invoice',255)->nullable();
            $table->string('id_business_partner',255)->nullable();
            $table->string('id_branch',255)->nullable();
            $table->string('amount',255)->nullable();
            $table->string('dpp',255)->nullable();
            $table->string('dpp_tax',255)->nullable();
            $table->string('tax',255)->nullable();
            $table->string('tax_value',255)->nullable();
            $table->datetime('tax_date')->nullable();
            $table->string('netto',255)->nullable();
            $table->string('outstanding',255)->nullable();
            $table->text('value_detail')->nullable();
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
        Schema::dropIfExists('invoice_bap');
    }
}
