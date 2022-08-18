<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeReimbursementDocuments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_reimbursement_documents', function (Blueprint $table) {
            $table->Increments('id_employee_sales_payment');
            $table->unsignedInteger('id_employee_reimbursement')->nullable();
            $table->foreign('id_employee_reimbursement', 'fk_id_employee_reimbursement')->references('id_employee_reimbursement')->on('employee_reimbursements')->onDelete('restrict');
            $table->unsignedInteger('id_approved')->nullable();
            $table->foreign('id_approved', 'fk_id_approved_reimbursement')->references('id')->on('users')->onDelete('restrict');
            $table->enum('document_type',['Manager Approved','Director Approved','HRGA Appporved','Fat Dept Approved','Approved'])->nullable();
            $table->date('process_date')->nullable();
            $table->text('process_notes')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_reimbursement_documents');
    }
}
