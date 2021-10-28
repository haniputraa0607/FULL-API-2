<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Doctrine\DBAL\Types\StringType; use Doctrine\DBAL\Types\Type;

class ChangeCityPostalCodeToNullableOnCitiesTable extends Migration
{
	public function __construct() 
    {
    	if (!Type::hasType('char')) {
			Type::addType('char', StringType::class);
		}
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->char('city_postal_code', 5)->nullable(true)->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->char('city_postal_code', 5)->nullable(false)->change();
        });
    }
}
