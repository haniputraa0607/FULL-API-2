<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDynamicToHairstylistGroupCommisionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_group_commissions', function (Blueprint $table) {
            $table->tinyInteger('dynamic')->default(0)->after('id_product');
            $table->integer('commission_percent')->nullable()->change();
        });

        Schema::create('hairstylist_group_commission_dynamics', function (Blueprint $table) {
            $table->Increments('id_hairstylist_group_commission_dynamic');
            $table->unsignedInteger('id_hairstylist_group_commission');
            $table->string('operator');
            $table->integer('qty');
            $table->integer('value');

            $table->foreign('id_hairstylist_group_commission','fk_id_hairstylist_group_commission')->references('id_hairstylist_group_commission')->on('hairstylist_group_commissions')->onDelete('cascade');
        });

        Schema::table('product_commission_default', function (Blueprint $table) {
            $table->tinyInteger('dynamic')->default(0)->after('id_product');
            $table->integer('commission')->nullable()->change();
        });

        Schema::create('product_commission_default_dynamics', function (Blueprint $table) {
            $table->Increments('id_product_commission_default_dynamic');
            $table->unsignedInteger('id_product_commission_default');
            $table->string('operator');
            $table->integer('qty');
            $table->integer('value');

            $table->foreign('id_product_commission_default','fk_id_product_commission_default')->references('id_product_commission_default')->on('product_commission_default')->onDelete('cascade');
        });

        Schema::create('global_commission_product_dynamics', function (Blueprint $table) {
            $table->Increments('id_global_commission_product_dynamics');
            $table->string('operator');
            $table->integer('qty');
            $table->integer('value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_group_commissions', function (Blueprint $table) {
            $table->dropColumn('dynamic');
            $table->integer('commission_percent')->nullable(false)->change();
        });

        Schema::dropIfExists('hairstylist_group_commission_dynamics');

        Schema::table('product_commission_default', function (Blueprint $table) {
            $table->dropColumn('dynamic');
            $table->integer('commission')->nullable(false)->change();
        });

        Schema::dropIfExists('product_commission_default_dynamics');
        Schema::dropIfExists('global_commission_product_dynamics');
    }
}
