<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum DevicePlatform: string
{
    case IOS = 'ios';
    case ANDROID = 'android';
}
