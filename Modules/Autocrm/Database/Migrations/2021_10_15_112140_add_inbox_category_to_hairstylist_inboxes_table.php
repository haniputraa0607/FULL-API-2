<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInboxCategoryToHairstylistInboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_inboxes', function (Blueprint $table) {
        	$table->string('inboxes_category')->nullable()->after('inboxes_id_reference');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_inboxes', function (Blueprint $table) {
        	$table->dropColumn('inboxes_category');
        });
    }
}
