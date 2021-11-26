<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_shops', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_shop');
            $table->unsignedInteger('id_transaction');
            $table->string('order_id');

            $table->unsignedInteger('id_admin_taken')->nullable();
            $table->unsignedInteger('id_admin_ready')->nullable();

            $table->enum('shop_status', ['Pending', 'Received', 'Ready', 'Delivery', 'Arrived', 'Completed', 'Rejected by Admin', 'Rejected by Customer']);

            $table->dateTime('received_at')->nullable();
            $table->dateTime('ready_at')->nullable();
            $table->dateTime('delivery_at')->nullable();
            $table->dateTime('arrived_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->string('reject_reason')->nullable();

            $table->string('destination_name');
            $table->string('destination_phone');
            $table->text('destination_address');
            $table->string('destination_short_address')->nullable();
            $table->string('destination_address_name')->nullable();
            $table->mediumText('destination_note')->nullable();
            $table->string('destination_latitude');
            $table->string('destination_longitude');
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
        Schema::dropIfExists('transaction_shops');
    }
}
