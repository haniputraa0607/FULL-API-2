<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EmployeeReimbursementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_reimbursements', function (Blueprint $table) {
            $table->increments('id_employee_reimbursement');
            $table->integer('id_user')->unsigned();
            $table->integer('id_user_approved')->unsigned()->nullable();
            $table->string('name_reimbursement')->nullable();
            $table->date('date_reimbursement')->nullable();
            $table->string('notes')->nullable();
            $table->string('approve_notes')->nullable();
            $table->string('attachment')->nullable();
            $table->integer('price')->nullable();
            $table->dateTime('date_submission')->nullable();
            $table->dateTime('date_validation')->nullable();
            $table->string('validator_reimbursement')->nullable();
            $table->dateTime('date_send_reimbursement')->nullable();
            $table->enum('status',['Pending','Approved','Successed','Rejected'])->default('Pending');
            $table->timestamps();
            $table->foreign('id_user', 'fk_employee_reimbursements')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('id_user_approved', 'fk_employee_reimbursements_user_approved')->references('id')->on('users')->onDelete('restrict');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_reimbursements');
    }
}
