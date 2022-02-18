<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnProductIcount extends Migration
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
            $table->dropUnique(['id_item']);
        });
        DB::statement('ALTER TABLE `product_icounts` CHANGE `is_deleted` `is_deleted` ENUM("true","false") default "false" NOT NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_icounts', function (Blueprint $table) {
            $table->unique('id_item');
        });
        DB::statement('ALTER TABLE `product_icounts` CHANGE `is_deleted` `is_deleted` ENUM("ture","false") default "false" NOT NULL;');
    }
}
