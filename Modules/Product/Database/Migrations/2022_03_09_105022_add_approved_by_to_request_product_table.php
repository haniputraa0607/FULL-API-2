<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApprovedByToRequestProductTable extends Migration
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
        DB::statement('ALTER TABLE `request_products` CHANGE `status` `status` ENUM("Pending","Completed By User","Completed By Finance","Rejected") default "Pending" NOT NULL;');
        Schema::table('request_products', function (Blueprint $table) {
            $table->string('id_purchase_request')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `request_products` CHANGE `status` `status` ENUM("Pending","On Progress","Completed") default "Pending" NOT NULL;');
        Schema::table('request_products', function (Blueprint $table) {
            $table->dropColumn('id_purchase_request');
        });
    }
}
