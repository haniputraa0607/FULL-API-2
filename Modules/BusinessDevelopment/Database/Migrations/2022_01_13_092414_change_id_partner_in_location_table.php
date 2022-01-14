<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdPartnerInLocationTable extends Migration
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
            $table->integer('submited_by')->nullable()->unsigned()->after('id_partner');
            $table->string('no_loi')->nullable()->after('value_detail');
            $table->date('date_loi')->nullable()->after('no_loi');
            $table->integer('id_partner')->nullable()->unsigned()->change();

            $table->foreign('submited_by', 'fk_location_submited_partner')->references('id_partner')->on('partners')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign('fk_location_submited_partner');
            $table->dropIndex('fk_location_submited_partner');

            $table->dropForeign('fk_location_partner');
            $table->dropIndex('fk_location_partner');
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('submited_by');
            $table->dropColumn('no_loi');
            $table->dropColumn('date_loi');
        });
        DB::statement('ALTER TABLE `locations` MODIFY `id_partner` int unsigned NOT NULL;');
        
        Schema::table('locations', function (Blueprint $table) {
            $table->foreign('id_partner', 'fk_location_partner')->references('id_partner')->on('partners')->onDelete('restrict');
        });
    }
}
