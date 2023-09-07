<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdDepartmentIcountInDepartmentsTable extends Migration
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
        \DB::statement("ALTER TABLE `departments` CHANGE COLUMN `id_department_icount` `id_department_icount` INT NULL DEFAULT NULL");
        Schema::table('departments', function (Blueprint $table) {
            $table->integer('id_company')->nullable()->after('id_department_icount');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->text('id_department_icount')->nullable()->change();
            $table->dropColumn('id_company');
        });
    }
}
