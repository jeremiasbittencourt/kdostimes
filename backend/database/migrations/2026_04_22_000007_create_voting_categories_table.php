<?php

declare(strict_types=1);

use App\Domain\Enums\VoteCategoryType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voting_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('type', array_column(VoteCategoryType::cases(), 'value'));
            $table->smallInteger('points_per_vote');
            $table->smallInteger('bonus_for_winner')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voting_categories');
    }
};
