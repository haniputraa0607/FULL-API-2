<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdMokaBusinessOnOutletsTable extends Migration
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
        Schema::table('outlets', function (Blueprint $table) {
            $table->unsignedBigInteger('id_outlet_seed')->nullable()->change();
            $table->unsignedBigInteger('id_moka_account_business')->nullable()->change();
            $table->bigInteger('id_moka_outlet')->nullable()->change();
            $table->bigInteger('advance_order')->nullable()->change();
        });
        DB::statement('ALTER TABLE `outlets` CHANGE `plastic_used_status` `plastic_used_status` ENUM("Active","Inactive") NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->unsignedBigInteger('id_outlet_seed')->change();
            $table->unsignedBigInteger('id_moka_account_business')->change();
            $table->bigInteger('id_moka_outlet')->change();
            $table->bigInteger('advance_order')->change();
        });
        DB::statement('ALTER TABLE `outlets` CHANGE `plastic_used_status` `plastic_used_status` ENUM("Active","Inactive") NOT NULL;');
    }
}
