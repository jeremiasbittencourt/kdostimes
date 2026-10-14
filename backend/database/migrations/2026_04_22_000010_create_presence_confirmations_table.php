<?php

declare(strict_types=1);

use App\Domain\Enums\PresenceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('presence_confirmations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pelada_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', array_column(PresenceStatus::cases(), 'value'));
            $table->timestamp('confirmed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['pelada_session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presence_confirmations');
    }
};
