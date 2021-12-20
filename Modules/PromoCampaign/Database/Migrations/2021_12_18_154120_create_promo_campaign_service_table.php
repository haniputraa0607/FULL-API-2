<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCampaignServiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_campaign_services', function (Blueprint $table) {
            $table->unsignedInteger('id_promo_campaign');
            $table->enum('service', ['Outlet Service', 'Home Service', 'Online Shop', 'Academy']);

            $table->foreign('id_promo_campaign')->on('promo_campaigns')->references('id_promo_campaign')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_campaign_service');
    }
}
