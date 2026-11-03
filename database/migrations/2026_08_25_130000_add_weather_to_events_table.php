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
        Schema::table('events', function (Blueprint $table) {
            $table->string('weather')->nullable()->after('time_of_day');
            $table->decimal('temperature_max', 4, 1)->nullable()->after('weather');
            $table->decimal('temperature_min', 4, 1)->nullable()->after('temperature_max');
            $table->unsignedTinyInteger('precipitation_probability')->nullable()->after('temperature_min');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'weather',
                'temperature_max',
                'temperature_min',
                'precipitation_probability',
            ]);
        });
    }
};
