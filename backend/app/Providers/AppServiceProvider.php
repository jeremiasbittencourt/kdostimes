<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Notification\PushDispatcher;
use App\Domain\TeamDraw\Strategies\SnakeDraftStrategy;
use App\Domain\TeamDraw\TeamDrawStrategyInterface;
use App\Services\Notification\FcmPushDispatcher;
use App\Services\Notification\LogPushDispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TeamDrawStrategyInterface::class,
            SnakeDraftStrategy::class,
        );

        $this->app->bind(PushDispatcher::class, static function (Application $app): PushDispatcher {
            if ((bool) config('cade-os-times.push.enabled') === true) {
                return $app->make(FcmPushDispatcher::class);
            }

            return $app->make(LogPushDispatcher::class);
        });
    }

    public function boot(): void
    {
    }
}
