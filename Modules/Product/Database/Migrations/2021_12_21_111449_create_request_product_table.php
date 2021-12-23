<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_products', function (Blueprint $table) {
            $table->increments('id_request_product');
            $table->string('code');
            $table->integer('id_outlet')->unsigned();
            $table->enum('type', ['Sell','Use']);
            $table->date('requirement_date');
            $table->integer('id_user_request')->unsigned();
            $table->text('note_request');
            $table->integer('id_user_approve')->unsigned()->nullable();
            $table->text('note_approve')->nullable();
            $table->enum('status', ['Pending','On Progress','Completed'])->default('Pending');
            $table->timestamps();

            $table->foreign('id_outlet', 'fk_outlet_request_product')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('id_user_request', 'fk_user_request_request_product')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_user_approve', 'fk_user_approve_request_product')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_products');
    }
}
