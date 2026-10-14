<?php

declare(strict_types=1);

use App\Domain\Enums\Position;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('player_positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('position', array_column(Position::cases(), 'value'));
            $table->unsignedTinyInteger('priority');
            $table->timestamps();

            $table->unique(['user_id', 'position']);
            $table->unique(['user_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_positions');
    }
};
