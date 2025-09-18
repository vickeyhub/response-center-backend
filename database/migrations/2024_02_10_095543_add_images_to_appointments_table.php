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
            $table->string('driver_license_front')->nullable();
            $table->string('driver_license_back')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
