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
        Schema::table('revspring_webhook_data', function (Blueprint $table) {
            $table->string("created_by")->after('revspring_token_id');
            $table->string("first_name");
            $table->string("last_name");
            $table->string("address1");
            $table->string("city");
            $table->string("state");
            $table->string("zip");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revspring_webhook_data', function (Blueprint $table) {
            $table->dropColumn("created_by");
            $table->dropColumn("first_name");
            $table->dropColumn("last_name");
            $table->dropColumn("address1");
            $table->dropColumn("city");
            $table->dropColumn("state");
            $table->dropColumn("zip");
        });
    }
};
