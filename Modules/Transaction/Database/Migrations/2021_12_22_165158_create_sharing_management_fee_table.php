<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSharingManagementFeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sharing_management_fee', function (Blueprint $table) {
            $table->increments('id_sharing_management_fee');
            $table->integer('id_partner');
            $table->enum('type',['Revenue Sharing','Management Fee']);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('total_transaksi')->nullable();
            $table->integer('total_beban')->nullable();
            $table->integer('tax')->nullable();
            $table->boolean('percent')->nullable();
            $table->integer('sharing')->nullable();
            $table->integer('disc')->nullable();
            $table->integer('transfer')->nullable();
            $table->text('id_transaction')->nullable();
            $table->text('data')->nullable();
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
        Schema::dropIfExists('sharing_management_fee');
    }
}
