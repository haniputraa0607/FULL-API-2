<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTransactionProductService extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_product_services', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_product_service');
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_transaction_product');
            $table->unsignedInteger('id_user_hair_stylist');
            $table->date('schedule_date');
            $table->time('schedule_time');
            $table->enum('service_status', ['In Progress', 'Completed'])->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->smallInteger('flag_update_schedule')->default(0);
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
        Schema::dropIfExists('transaction_product_services');
    }
}
