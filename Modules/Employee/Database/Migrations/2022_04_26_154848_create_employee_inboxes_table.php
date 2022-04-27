<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeInboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_inboxes', function (Blueprint $table) {
            $table->increments('id_employee_inboxes');
            $table->unsignedInteger('id_campaign')->nullable();
            $table->unsignedInteger('id_employee');
            $table->string('inboxes_subject');
            $table->text('inboxes_content')->nullable();
            $table->string('inboxes_clickto');
            $table->string('inboxes_link', 255)->nullable();
            $table->string('inboxes_id_reference', 20)->nullable();
            $table->string('inboxes_category', 191)->nullable();
            $table->string('inboxes_from', 50)->nullable();
            $table->dateTime('inboxes_send_at')->nullable();
            $table->char('read', 1);
            $table->unsignedInteger('id_brand')->nullable();

            $table->timestamps();

			$table->foreign('id_employee', 'fk_employee_inboxes_employee')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_campaign', 'fk_campaign_inboxes_employee')->references('id_campaign')->on('campaigns')->onUpdate('CASCADE')->onDelete('CASCADE');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_inboxes');
    }
}
