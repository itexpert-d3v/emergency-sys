<?php

namespace App\Events;

use App\Support\I18n\LocaleCatalog;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LiveMapChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public const PAYLOAD_CACHE_KEY = 'live-map:payload';

    public static function dispatch(): void
    {
        $dispatch = static function (): void {
            $locales = rescue(fn () => array_keys(app(LocaleCatalog::class)->all()), [app()->getLocale()]);
            foreach ($locales as $locale) {
                rescue(fn () => Cache::forget(self::PAYLOAD_CACHE_KEY.':'.$locale));
            }
            rescue(fn () => Cache::forget('admin-dashboard:summary'));
            rescue(fn () => event(new self()));
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($dispatch);

            return;
        }

        $dispatch();
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.live-map')];
    }

    public function broadcastAs(): string
    {
        return 'live-map.changed';
    }
}
