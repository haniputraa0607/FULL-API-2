<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameDocumentCashAdvance extends Migration
{
   
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
    {
        Schema::table('employee_cash_advance_documents', function (Blueprint $table) {
            $table->renameColumn('status', 'document_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_cash_advance_documents', function (Blueprint $table) {
            $table->renameColumn('document_type', 'status');

        });
    }
}
