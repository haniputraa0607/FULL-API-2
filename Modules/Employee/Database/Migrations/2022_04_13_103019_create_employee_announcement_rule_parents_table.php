<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeAnnouncementRuleParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_announcement_rule_parents', function (Blueprint $table) {
            $table->increments('id_employee_announcement_rule_parent');
			$table->bigInteger('id_employee_announcement')->unsigned()->index('fk_employee_announcement_rule_parents_employee_announcements');
			$table->enum('employee_announcement_rule', array('and','or'));
			$table->enum('employee_announcement_rule_next', array('and','or'));
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
        Schema::dropIfExists('employee_announcement_rule_parents');
    }
}
