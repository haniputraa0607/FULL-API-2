<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_services', function (Blueprint $table) {
            $table->unsignedInteger('id_deals');
            $table->enum('service', ['Outlet Service', 'Home Service', 'Online Shop', 'Academy']);

            $table->foreign('id_deals')->on('deals')->references('id_deals')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_services');
    }
}
