<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameDocumentReimbursement extends Migration
{
   
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
    {
        Schema::table('employee_reimbursement_documents', function (Blueprint $table) {
            $table->renameColumn('id_employee_sales_payment', 'id_employee_reimbursement_document');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_reimbursement_documents', function (Blueprint $table) {
            $table->renameColumn('id_employee_reimbursement_document', 'id_employee_sales_payment');

        });
    }
}
