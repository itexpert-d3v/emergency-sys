<?php

namespace App\Console\Commands;

use App\Models\BannedIp;
use App\Services\Security\ProgressiveRateLimitState;
use Illuminate\Console\Command;

class UnbanIpCommand extends Command
{
    protected $signature = 'ip:unban {ip : The IPv4 or IPv6 address to unban}';

    protected $description = 'Remove an IP ban and clear progressive rate-limit state';

    public function handle(ProgressiveRateLimitState $rateLimitState): int
    {
        $ipAddress = (string) $this->argument('ip');
        if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            $this->components->error('Provide a valid IPv4 or IPv6 address.');

            return self::FAILURE;
        }

        $ipAddress = BannedIp::normalize($ipAddress);
        BannedIp::query()->where('ip_address', $ipAddress)->delete();
        $rateLimitState->clearIp($ipAddress);

        $this->components->info("Removed the IP ban and rate-limit counters for {$ipAddress}.");

        return self::SUCCESS;
    }
}
