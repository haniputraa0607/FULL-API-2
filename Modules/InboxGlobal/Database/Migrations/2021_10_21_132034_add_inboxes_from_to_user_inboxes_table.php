<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInboxesFromToUserInboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_inboxes', function (Blueprint $table) {
            $table->string('inboxes_from', 50)->after('inboxes_category')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_inboxes', function (Blueprint $table) {
            $table->dropColumn('inboxes_from');
        });
    }
}
