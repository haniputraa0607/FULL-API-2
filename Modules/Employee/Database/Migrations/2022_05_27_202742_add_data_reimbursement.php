<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDataReimbursement extends Migration
{
     public function up()
    {
        Schema::table('employee_reimbursements', function (Blueprint $table) {
            $table->integer('id_product_icount');
            $table->integer('qty')->default(1);
            $table->string('id_purchase_invoice')->nullable();
            $table->text('value_detail')->nullable();
            $table->date('due_date')->nullable();
            $table->dropColumn('name_reimbursement')->nullable();
        });
    }
    public function down()
    {
        Schema::table('employee_reimbursements', function (Blueprint $table) {
            $table->dropColumn('id_product_icount')->nullable();
            $table->dropColumn('qty')->nullable();
            $table->dropColumn('id_purchase_invoice')->nullable();
            $table->dropColumn('value_detail')->nullable();
            $table->dropColumn('due_date')->nullable();
            $table->string('name_reimbursement')->nullable();
        });
    }
}
