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
        Schema::create('appointment_payment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->string('transaction_id');
            $table->string('total_amount');
            $table->string('status');
            $table->string('message');
            $table->string('gateway_transaction_id');
            $table->string('authorization_code');
            $table->string('gateway_name');
            $table->string('payment_date_time');
            $table->string('network');
            $table->string('card_holder_name');
            $table->enum("payment_status",[0,1])->comment("0 for unpaid, 1 for paid.");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_payment');
    }
};
