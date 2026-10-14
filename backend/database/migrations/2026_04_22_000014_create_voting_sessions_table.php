<?php

declare(strict_types=1);

use App\Domain\Enums\VotingSessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voting_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pelada_session_id')->constrained()->cascadeOnDelete();
            $table->dateTime('opens_at');
            $table->dateTime('closes_at');
            $table->enum('status', array_column(VotingSessionStatus::cases(), 'value'))
                ->default(VotingSessionStatus::PENDING->value);
            $table->timestamps();

            $table->unique('pelada_session_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voting_sessions');
    }
};
