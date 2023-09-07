<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeChartOfAccountsTable extends Migration
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
        \DB::statement("ALTER TABLE `chart_of_account` CHANGE COLUMN `ChartOfAccountID` `ChartOfAccountID` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `chart_of_account` CHANGE COLUMN `GroupAccountID` `GroupAccountID` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `chart_of_account` CHANGE COLUMN `ParentID` `ParentID` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `chart_of_account` CHANGE COLUMN `Type` `Type` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `chart_of_account` CHANGE COLUMN `CompanyID` `CompanyID` INT NULL DEFAULT NULL");
        Schema::table('chart_of_account', function (Blueprint $table) {
            $table->boolean('IsSuspended')->nullable()->after('IsChildest');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chart_of_account', function (Blueprint $table) {
            $table->string('ChartOfAccountID')->nullable()->change();
            $table->string('GroupAccountID')->nullable()->change();
            $table->string('ParentID')->nullable()->change();
            $table->string('Type')->nullable()->change();
            $table->string('CompanyID')->nullable()->change();
            $table->dropColumn('IsSuspended');
        });
    }
}
