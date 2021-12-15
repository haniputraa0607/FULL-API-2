<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnProductIcountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
    {
        Schema::table('product_icounts', function (Blueprint $table) {
            $table->integer('buy_price_1')->change();
            $table->integer('buy_price_2')->change();
            $table->integer('buy_price_3')->change();
            $table->integer('unit_price_1')->change();
            $table->integer('unit_price_2')->change();
            $table->integer('unit_price_3')->change();
            $table->integer('unit_price_4')->change();
            $table->integer('unit_price_5')->change();
            $table->integer('unit_price_6')->change();
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
            $table->decimal('buy_price_1',10,4)->change();
            $table->decimal('buy_price_2',10,4)->change();
            $table->decimal('buy_price_3',10,4)->change();
            $table->decimal('unit_price_1',10,4)->change();
            $table->decimal('unit_price_2',10,4)->change();
            $table->decimal('unit_price_3',10,4)->change();
            $table->decimal('unit_price_4',10,4)->change();
            $table->decimal('unit_price_5',10,4)->change();
            $table->decimal('unit_price_6',10,4)->change();
        });
    }
}
