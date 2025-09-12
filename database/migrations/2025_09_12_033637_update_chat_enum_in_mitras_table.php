<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to use a different approach since it doesn't support MODIFY COLUMN
        // We'll change the chat field to a string and add a check constraint
        Schema::table('mitras', function (Blueprint $table) {
            $table->string('chat_new')->nullable();
        });

        // Copy existing data
        DB::statement("UPDATE mitras SET chat_new = chat");

        // Drop the old column and rename the new one
        Schema::table('mitras', function (Blueprint $table) {
            $table->dropColumn('chat');
        });

        Schema::table('mitras', function (Blueprint $table) {
            $table->renameColumn('chat_new', 'chat');
        });

        // Add check constraint to simulate enum behavior
        DB::statement("UPDATE mitras SET chat = 'masuk' WHERE chat NOT IN ('masuk', 'followup', 'followup_2', 'followup_3')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert followup_2 and followup_3 back to followup
        DB::statement("UPDATE mitras SET chat = 'followup' WHERE chat IN ('followup_2', 'followup_3')");
    }
};
