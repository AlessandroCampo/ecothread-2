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
        // Rimuovi la foreign key da events.event_type
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['event_type']);
        });

        // Droppa la tabella event_types (non più necessaria)
        Schema::dropIfExists('event_types');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ricrea la tabella event_types
        Schema::create('event_types', function (Blueprint $table) {
            $table->string('code', 50)->primary();
            $table->string('label');
            $table->string('icon', 10)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Ricrea la foreign key
        Schema::table('events', function (Blueprint $table) {
            $table->foreign('event_type')
                  ->references('code')
                  ->on('event_types');
        });
    }
};
