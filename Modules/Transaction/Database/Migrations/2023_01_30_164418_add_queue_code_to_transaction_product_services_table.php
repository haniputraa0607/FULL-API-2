<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQueueCodeToTransactionProductServicesTable extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_product_services', function (Blueprint $table) {
            $table->integer('queue')->nullable()->after('is_conflict');
            $table->string('queue_code')->nullable()->after('queue');
            $table->unsignedInteger('id_user_hair_stylist')->nullable()->change();
            $table->time('schedule_time')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_product_services', function (Blueprint $table) {
            $table->dropColumn('queue');
            $table->dropColumn('queue_code');
            $table->unsignedInteger('id_user_hair_stylist')->nullable(false)->change();
            $table->time('schedule_time')->nullable(false)->change();
        });
    }
}
