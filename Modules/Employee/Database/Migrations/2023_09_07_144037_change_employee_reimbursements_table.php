<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeEmployeeReimbursementsTable extends Migration
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
        \DB::statement("ALTER TABLE `employee_reimbursements` CHANGE COLUMN `id_purchase_invoice` `id_purchase_invoice` INT NULL DEFAULT NULL");
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_reimbursements', function (Blueprint $table) {
            $table->string('id_purchase_invoice')->nullable()->change();
        });
    }
}
