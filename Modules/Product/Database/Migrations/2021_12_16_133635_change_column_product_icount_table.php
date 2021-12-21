<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnEnumProductIcountTable extends Migration
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
        DB::statement('ALTER TABLE `product_icounts` CHANGE `is_suspended` `is_suspended` ENUM("true","false") default "false" NULL;');
        DB::statement('ALTER TABLE `product_icounts` CHANGE `is_sellable` `is_sellable` ENUM("true","false") default "true" NULL;');
        DB::statement('ALTER TABLE `product_icounts` CHANGE `is_buyable` `is_buyable` ENUM("true","false") default "true" NULL;');
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `product_icounts` CHANGE `is_suspended` `is_suspended` ENUM("true","false") default "false" NOT NULL;');
        DB::statement('ALTER TABLE `product_icounts` CHANGE `is_sellable` `is_sellable` ENUM("true","false") default "true" NOT NULL;');
        DB::statement('ALTER TABLE `product_icounts` CHANGE `is_buyable` `is_buyable` ENUM("true","false") default "true" NOT NULL;');
        
    }
}
