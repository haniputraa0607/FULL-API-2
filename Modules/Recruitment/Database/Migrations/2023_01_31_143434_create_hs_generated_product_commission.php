<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsGeneratedProductCommission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_generated_product_commission_queues', function (Blueprint $table) {
            $table->bigIncrements('id_hairstylist_generated_product_commission_queue');
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->enum('status_export', array('Running', 'Ready'))->default('Running');
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
        Schema::dropIfExists('hairstylist_generated_product_commission_queues');
    }
}
