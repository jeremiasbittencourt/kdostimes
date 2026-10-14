<?php

declare(strict_types=1);

use App\Domain\Enums\MemberRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pelada_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pelada_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', array_column(MemberRole::cases(), 'value'))
                ->default(MemberRole::MEMBER->value);
            $table->boolean('is_fixed_goalkeeper')->default(false);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['pelada_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelada_members');
    }
};
