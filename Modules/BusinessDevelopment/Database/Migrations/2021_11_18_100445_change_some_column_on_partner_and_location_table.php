<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeSomeColumnOnPartnerAndLocationTable extends Migration
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
            $table->integer('id_chart_account')->after('id_location')->nullable();
            $table->enum('is_deleted',['true','false'])->default('false')->after('id_brand')->nullable();
        });
        Schema::table('partners', function (Blueprint $table) {
            $table->enum('is_deleted',['true','false'])->default('false')->after('due_date')->nullable();
        });
        DB::statement('ALTER TABLE `partners` CHANGE `is_suspended` `is_suspended` ENUM("0","1") default "0" NOT NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `is_tax` `is_tax` ENUM("0","1") default "1" NOT NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `price_level` `price_level` ENUM("0","1") default "1" NOT NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('id_chart_account');
            $table->dropColumn('is_deleted');
        });
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
        });
        DB::statement('ALTER TABLE `partners` CHANGE `is_suspended` `is_suspended` integer default "0" NOT NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `is_tax` `is_tax` integer default "0" NOT NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `price_level` `price_level` integer default "0" NOT NULL;');
    }
}
