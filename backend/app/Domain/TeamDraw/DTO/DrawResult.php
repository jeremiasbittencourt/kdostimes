<?php

declare(strict_types=1);

namespace App\Domain\TeamDraw\DTO;

final readonly class DrawResult
{
    /**
     * @param  list<TeamComposition>  $teams
     * @param  list<string>  $warnings
     */
    public function __construct(
        public array $teams,
        public array $warnings,
    ) {
    }
}
