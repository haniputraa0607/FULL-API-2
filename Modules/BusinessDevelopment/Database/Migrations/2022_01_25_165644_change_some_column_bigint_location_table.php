<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeSomeColumnBigintLocationTable extends Migration
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
        Schema::table('locations', function (Blueprint $table) {
            $table->unsignedBigInteger('rental_price')->change();
            $table->unsignedBigInteger('service_charge')->change();
            $table->unsignedBigInteger('promotion_levy')->change();
            $table->unsignedBigInteger('renovation_cost')->change();
            $table->unsignedBigInteger('partnership_fee')->change();
            $table->unsignedBigInteger('total_payment')->change();
            $table->unsignedBigInteger('sharing_value')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->integer('rental_price')->change();
            $table->integer('service_charge')->change();
            $table->integer('promotion_levy')->change();
            $table->integer('renovation_cost')->change();
            $table->integer('partnership_fee')->change();
            $table->integer('total_payment')->change();
            $table->integer('sharing_value')->change();
        });
    }
}
