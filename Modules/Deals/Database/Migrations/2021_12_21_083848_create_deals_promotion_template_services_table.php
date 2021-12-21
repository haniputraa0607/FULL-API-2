<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPromotionTemplateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_promotion_template_services', function (Blueprint $table) {
            $table->unsignedInteger('id_deals');
            $table->enum('service', ['Outlet Service', 'Home Service', 'Online Shop', 'Academy']);

            $table->foreign('id_deals', 'fk_deals_promotion')->references('id_deals_promotion_template')->on('deals_promotion_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_promotion_template_services');
    }
}
