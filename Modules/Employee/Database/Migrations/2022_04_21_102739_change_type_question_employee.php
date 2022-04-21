<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeQuestionEmployee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `question_employees` CHANGE `type` `type` ENUM("Type 1","Type 2","Type 3","Type 4") default "Type 1";');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         DB::statement('ALTER TABLE `question_employees` CHANGE `type` `type` ENUM("Type 1","Type 2","Type 3") default "Type 1";');
    }
}
