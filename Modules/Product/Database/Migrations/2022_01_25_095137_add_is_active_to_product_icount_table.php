<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsActiveToProductIcountTable extends Migration
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
            $table->enum('is_actived', ['true','false'])->default('true')->after('id_deleted');
        });
        DB::statement('ALTER TABLE `product_icounts` CHANGE `id_deleted` `is_deleted` ENUM("ture","false") default "false" NOT NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_icounts', function (Blueprint $table) {
            $table->dropColumn('is_actived');
        });
        DB::statement('ALTER TABLE `product_icounts` CHANGE `is_deleted` `id_deleted` ENUM("ture","false") default "false" NOT NULL;');
    }
}
