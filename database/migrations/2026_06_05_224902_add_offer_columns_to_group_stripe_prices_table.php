<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('group_stripe_prices', function (Blueprint $table) {
            $table->string('mode')->default('subscription')->after('stripe_price');
            $table->string('label')->nullable()->after('mode');
            $table->unsignedInteger('amount')->nullable()->after('label');
            $table->string('interval')->nullable()->after('amount');
            $table->boolean('is_active')->default(true)->after('interval');
            $table->unsignedInteger('sort')->default(0)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_stripe_prices', function (Blueprint $table) {
            $table->dropColumn([
                'mode',
                'label',
                'amount',
                'interval',
                'is_active',
                'sort',
            ]);
        });
    }
};
