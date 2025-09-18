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
        Schema::create('revspring_webhook_data', function (Blueprint $table) {
            $table->id();
            $table->string("is_in_use");
            $table->string("add_to_wallet");
            $table->string("token_data_source");
            $table->integer("revspring_id");
            $table->string("revspring_token_id");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revspring_webhook_data');
    }
};
