<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIcountResponseColumnUserhairstilistTable extends Migration
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
        \DB::statement("ALTER TABLE `user_hair_stylist` CHANGE COLUMN `id_business_partner` `id_business_partner` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `user_hair_stylist` CHANGE COLUMN `id_business_partner_ima` `id_business_partner_ima` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `user_hair_stylist` CHANGE COLUMN `id_company` `id_company` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `user_hair_stylist` CHANGE COLUMN `id_group_business_partner` `id_group_business_partner` INT NULL DEFAULT NULL");
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_hair_stylist', function (Blueprint $table) {
            $table->string('id_business_partner')->nullable()->change();
            $table->string('id_business_partner_ima')->nullable()->change();
            $table->string('id_company')->nullable()->change();
            $table->string('id_group_business_partner')->nullable()->change();
        });
    }
}
