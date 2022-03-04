<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAmountXenditToInteger extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE deals_payment_xendits MODIFY amount INTEGER null default 0;");
        \DB::statement("ALTER TABLE subscription_payment_xendits MODIFY amount INTEGER null default 0;");
        \DB::statement("ALTER TABLE transaction_academy_installment_payment_xendits MODIFY amount INTEGER null default 0;");
        \DB::statement("ALTER TABLE transaction_payment_xendits MODIFY amount INTEGER null default 0;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE deals_payment_xendits MODIFY amount VARCHAR(255) null;");
        \DB::statement("ALTER TABLE subscription_payment_xendits MODIFY amount VARCHAR(255) null;");
        \DB::statement("ALTER TABLE transaction_academy_installment_payment_xendits MODIFY amount VARCHAR(255) null;");
        \DB::statement("ALTER TABLE transaction_payment_xendits MODIFY amount VARCHAR(255) null;");
    }
}
