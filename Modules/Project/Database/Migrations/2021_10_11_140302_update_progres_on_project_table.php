<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateProgresOnProjectTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `projects` CHANGE `progres` `progres` ENUM("Survey Location","Desain Location","Contract","Fit Out","Handover","Success") default "Survey Location";');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `projects` CHANGE `progres` `progres` ENUM("Survey Location","Desain Location","Contract","Fit Out","Handover","Success") default "Survey Location";');
    }
}
