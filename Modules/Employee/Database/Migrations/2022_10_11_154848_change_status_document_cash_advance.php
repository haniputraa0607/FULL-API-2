<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeStatusDocumentCashAdvance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       DB::statement('ALTER TABLE `employee_cash_advance_documents` CHANGE `document_type` `document_type` ENUM("Manager Approval","HRGA/Direktur Approval","Finance Approval","Realisasi") default NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `employee_cash_advance_documents` CHANGE `document_type` `document_type` ENUM("Manager Approval","HRGA/Direktur Approval","Finance Approval","Realisasi") default NULL;');
    }
}
