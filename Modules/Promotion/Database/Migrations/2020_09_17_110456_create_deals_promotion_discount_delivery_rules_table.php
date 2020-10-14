<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPromotionDiscountDeliveryRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_promotion_discount_delivery_rules', function (Blueprint $table) {
            $table->increments('id_deals_promotion_discount_delivery_rule');
            $table->unsignedInteger('id_deals');
            $table->enum('discount_type', ['Percent', 'Nominal']);
            $table->integer('discount_value');
            $table->integer('max_percent_discount')->nullable();
            $table->timestamps();

            $table->foreign('id_deals', 'fk_deals_promotion_discount_delivery_rules_deals')->references('id_deals_promotion_template')->on('deals_promotion_templates')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_promotion_discount_delivery_rules');
    }
}
