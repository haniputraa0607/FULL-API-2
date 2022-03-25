<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInitBranchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('init_branchs', function (Blueprint $table) {
            $table->increments('id_init_branch');
            $table->integer('id_partner')->nullable();
            $table->integer('id_location')->unsigned()->nullable();
            $table->string('id_sales_order')->nullable();
            $table->string('id_company')->nullable();
            $table->string('no_voucher')->nullable();
            $table->integer('amount')->nullable();
            $table->integer('tax')->nullable();
            $table->integer('tax_value')->nullable();
            $table->integer('netto')->nullable();
            $table->string('id_sales_order_detail')->nullable();
            $table->string('id_item')->nullable();
            $table->integer('qty')->nullable();
            $table->string('unit')->nullable();
            $table->integer('ratio')->nullable();
            $table->integer('unit_ratio')->nullable();
            $table->integer('price')->nullable();
            $table->string('detail_name')->nullable();
            $table->integer('disc')->nullable();
            $table->integer('disc_value')->nullable();
            $table->integer('disc_rp')->nullable();
            $table->string('description')->nullable();
            $table->integer('outstanding')->nullable();
            $table->string('item')->nullable();

            $table->timestamps();

            $table->foreign('id_location', 'fk_init_branch_location')->references('id_location')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('init_branchs');
    }
}
