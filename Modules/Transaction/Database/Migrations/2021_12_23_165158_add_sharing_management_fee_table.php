<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSharingManagementFeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sharing_management_fee', function (Blueprint $table) {
            $table->string('PurchaseInvoiceID')->nullable();
            $table->enum('status',['Proccess','Success','Fail'])->default('Proccess');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('sharing_management_fee', function (Blueprint $table) {
            $table->dropColumn('PurchaseInvoiceID');
            $table->dropColumn('status',['Proccess','Success','Fail']);
        });
    }
}
