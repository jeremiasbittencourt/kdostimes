<?php

declare(strict_types=1);

use App\Domain\Enums\Position;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('draw_team_players', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('draw_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('assigned_position', array_column(Position::cases(), 'value'))->nullable();
            $table->boolean('is_goalkeeper')->default(false);
            $table->timestamps();

            $table->unique(['draw_team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draw_team_players');
    }
};
