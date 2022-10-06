<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeCashAdvance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_cash_advances', function (Blueprint $table) {
            $table->Increments('id_employee_cash_advance');
            $table->integer('id_user')->unsigned()->nullable();
            $table->integer('id_user_approved')->unsigned()->nullable();
            $table->string('title')->nullable();
            $table->date('date_cash_advance')->nullable();
            $table->integer('price')->nullable();
            $table->enum('status',['Pending','Manager Approval','HRGA/Direktur Approval','Finance Approval','Realisasi','Approve','Success','Rejected'])->default('Pending');
            $table->text('notes')->nullable();
            $table->string('attachment')->nullable();
            $table->date('date_validation')->nullable();
            $table->date('date_send_cash_advance')->nullable();
            $table->string('id_purchase_deposit_request')->nullable();
            $table->text('value_detail')->nullable();
            $table->date('date_disburse')->nullable();
            $table->date('tax_date')->nullable();
            $table->foreign('id_user', 'fk_id_user_cash_advance')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_user_approved', 'fk_id_user_approved_advance')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('employee_cash_advances');
    }
}
