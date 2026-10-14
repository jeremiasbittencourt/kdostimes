<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum Position: string
{
    case GOL = 'GOL';
    case ZAG = 'ZAG';
    case LAT = 'LAT';
    case MEI = 'MEI';
    case ATA = 'ATA';

    public function label(): string
    {
        return match ($this) {
            self::GOL => 'Goleiro',
            self::ZAG => 'Zagueiro',
            self::LAT => 'Lateral',
            self::MEI => 'Meia',
            self::ATA => 'Atacante',
        };
    }
}
