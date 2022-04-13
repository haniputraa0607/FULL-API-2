<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnitConversionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unit_conversion_logs', function (Blueprint $table) {
            $table->increments('id_unit_conversion_log');
            $table->string('code_conversion');
            $table->integer('id_user');
            $table->integer('id_outlet');
            $table->integer('id_product_icount');
            $table->string('unit');
            $table->integer('qty_before_conversion');
            $table->integer('qty_conversion');
            $table->string('unit_conversion');
            $table->string('conversion_type');
            $table->string('ratio');
            $table->integer('qty_after_conversion');
            $table->integer('qty_unit_converion');
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
        Schema::dropIfExists('unit_conversion_logs');
    }
}
