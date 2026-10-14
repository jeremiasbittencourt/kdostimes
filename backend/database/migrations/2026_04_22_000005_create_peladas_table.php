<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('peladas', function (Blueprint $table): void {
            $table->id();
            $table->string('short_code', 16)->unique();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('location')->nullable();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('time');
            $table->unsignedTinyInteger('players_per_team');
            $table->boolean('has_fixed_goalkeepers')->default(false);
            $table->unsignedTinyInteger('confirmation_deadline_hours')->default(2);
            $table->timestamps();

            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peladas');
    }
};
