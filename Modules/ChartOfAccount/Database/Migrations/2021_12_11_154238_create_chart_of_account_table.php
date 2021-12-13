<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChartOfAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chart_of_account', function (Blueprint $table) {
            $table->increments('id_chart_of_account');
            $table->string('ChartOfAccountID')->nullable();
            $table->string('CompanyID')->nullable();
            $table->string('GroupAccountID')->nullable();
            $table->string('AccountNo')->nullable();
            $table->string('Description')->nullable();
            $table->string('ParentID')->nullable();
            $table->boolean('IsChildest')->nullable();
            $table->boolean('IsBank')->nullable();
            $table->string('Type')->nullable();
            $table->boolean('IsDeleted')->nullable();
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
        Schema::dropIfExists('chart_of_account');
    }
}
