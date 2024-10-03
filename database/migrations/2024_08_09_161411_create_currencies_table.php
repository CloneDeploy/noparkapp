<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('symbol');
            $table->timestamps();
        });


        DB::table('currencies')->insert([
            ['name' => 'UK Pounds', 'code' => 'GBP', 'symbol' => '£'],
            ['name' => 'US Dollars', 'code' => 'USD', 'symbol' => '$'],
            ['name' => 'Euros', 'code' => 'EUR', 'symbol' => '€'],
            ['name' => 'Swiss Francs', 'code' => 'CHF', 'symbol' => 'CHF'],
            ['name' => 'Canadian Dollars', 'code' => 'CAD', 'symbol' => 'C$'],
            ['name' => 'Norwegian Krone', 'code' => 'NOK', 'symbol' => 'kr'],
            ['name' => 'Swedish Krona', 'code' => 'SEK', 'symbol' => 'kr'],
            ['name' => 'Danish Krone', 'code' => 'DKK', 'symbol' => 'kr'],
            ['name' => 'Icelandic Krona', 'code' => 'ISK', 'symbol' => 'kr'],
            ['name' => 'Czech Koruna', 'code' => 'CZK', 'symbol' => 'Kč'],
            ['name' => 'Polish Zloty', 'code' => 'PLN', 'symbol' => 'zł'],
            ['name' => 'Hungarian Forint', 'code' => 'HUF', 'symbol' => 'Ft'],
            ['name' => 'Bulgarian Lev', 'code' => 'BGN', 'symbol' => 'лв'],
            ['name' => 'Romanian Leu', 'code' => 'RON', 'symbol' => 'lei'],
            ['name' => 'Croatian Kuna', 'code' => 'HRK', 'symbol' => 'kn'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
