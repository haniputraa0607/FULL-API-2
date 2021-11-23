<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePurchaseSpkTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_spk', function (Blueprint $table) {
            $table->increments('id_purchase_spk');
            $table->integer('id_project')->unsigned();
            $table->foreign('id_project', 'fk_project_purchase_spk')->references('id_project')->on('projects')->onDelete('restrict');
            $table->string('id_request_purchase',255)->nullable();
            $table->string('id_business_partner',255)->nullable();
            $table->string('id_branch',255)->nullable();
            $table->text('value_detail')->nullable();
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
        Schema::dropIfExists('purchase_spk');
    }
}
