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
        Schema::table('appointments', function (Blueprint $table) {
            $table->string("insurance_card_front")->after('message')->nullable();
            $table->string("insurance_card_back")->after('message')->nullable();
            $table->string("policy_holder_name")->after('message')->nullable();
            $table->string("policy_holder_dob")->after('message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('add_column_to_appointments');
    }
};
