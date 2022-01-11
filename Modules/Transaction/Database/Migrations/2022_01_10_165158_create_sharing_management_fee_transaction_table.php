<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSharingManagementFeeTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sharing_management_fee_transaction', function (Blueprint $table) {
            $table->increments('id_sharing_management_fee_transaction');
            $table->integer('id_sharing_management_fee');
            $table->integer('id_transaction');
            $table->enum('status',['Proccess','Success','Fail'])->default('Proccess');
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
        Schema::dropIfExists('sharing_management_fee_transaction');
    }
}
