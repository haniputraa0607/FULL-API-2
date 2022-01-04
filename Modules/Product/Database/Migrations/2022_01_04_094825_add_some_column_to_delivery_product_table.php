<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToDeliveryProductTable extends Migration
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
        Schema::table('delivery_products', function (Blueprint $table) {
            $table->date('confirmation_date')->nullable()->after('id_user_accept');
            $table->text('confirmation_note')->nullable()->after('confirmation_date');
            $table->dropForeign('fk_user_accept_delivery_product');
            $table->dropIndex('fk_user_accept_delivery_product');
        });

        Schema::table('delivery_products', function (Blueprint $table) {
            $table->bigInteger('id_user_accept')->unsigned()->change();
            $table->foreign('id_user_accept', 'fk_user_accept_delivery_product')->references('id_user_hair_stylist')->on('user_hair_stylist')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_products', function (Blueprint $table) {
            $table->dropColumn('confirmation_date');
            $table->dropColumn('confirmation_note');
            $table->dropForeign('fk_user_accept_delivery_product');
            $table->dropIndex('fk_user_accept_delivery_product');
        }); 
        
        Schema::table('delivery_products', function (Blueprint $table) {
            $table->integer('id_user_accept')->unsigned()->change();
            $table->foreign('id_user_accept', 'fk_user_accept_delivery_product')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
