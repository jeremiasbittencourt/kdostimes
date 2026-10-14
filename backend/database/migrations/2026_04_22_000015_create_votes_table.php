<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('voting_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voting_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voter_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('voted_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['voting_session_id', 'voting_category_id', 'voter_user_id'],
                'votes_unique_voter_per_category'
            );
            $table->index(['voting_session_id', 'voting_category_id', 'voted_user_id'], 'votes_tally_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
