<?php

declare(strict_types=1);

namespace App\Domain\TeamDraw;

use App\Domain\TeamDraw\DTO\DrawInput;
use App\Domain\TeamDraw\DTO\DrawResult;

interface TeamDrawStrategyInterface
{
    public function draw(DrawInput $input): DrawResult;
}
