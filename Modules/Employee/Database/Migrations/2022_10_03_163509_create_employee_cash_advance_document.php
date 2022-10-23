<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeCashAdvanceDocument extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_cash_advance_documents', function (Blueprint $table) {
            $table->Increments('id_cash_advance_document');
            $table->unsignedInteger('id_employee_cash_advance')->nullable();
            $table->foreign('id_employee_cash_advance', 'fk_id_employee_cash_advance')->references('id_employee_cash_advance')->on('employee_cash_advances')->onDelete('restrict');
            $table->unsignedInteger('id_approved')->nullable();
            $table->foreign('id_approved', 'fk_id_approved_cash_advance')->references('id')->on('users')->onDelete('restrict');
             $table->enum('status',['Pending','Manager Approval','HRGA/Direktur Approval','Finance Approval','Realisasi','Approve','Success','Rejected'])->default('Pending');
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
        Schema::dropIfExists('employee_cash_advance_documents');
    }
}
