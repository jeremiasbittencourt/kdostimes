<?php

declare(strict_types=1);

use App\Domain\Enums\SessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pelada_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pelada_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->dateTime('deadline_at');
            $table->enum('status', array_column(SessionStatus::cases(), 'value'))
                ->default(SessionStatus::OPEN->value);
            $table->timestamps();

            $table->index(['pelada_id', 'scheduled_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelada_sessions');
    }
};
