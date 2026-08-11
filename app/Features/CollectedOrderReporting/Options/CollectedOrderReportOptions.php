<?php

namespace App\Features\CollectedOrderReporting\Options;

use DateTimeZone;
use InvalidArgumentException;

final readonly class CollectedOrderReportOptions
{
    public function __construct(
        public int $days = 3,
        public string $timezone = 'UTC',
        public ?int $limit = null,
    ) {
        if ($days < 1) {
            throw new InvalidArgumentException('Collected-order days must be a positive integer.');
        }
        if ($limit !== null && $limit < 1) {
            throw new InvalidArgumentException('Collected-order limit must be a positive integer.');
        }
        try {
            new DateTimeZone($timezone);
        } catch (\Throwable) {
            throw new InvalidArgumentException("Invalid timezone [{$timezone}].");
        }
    }
}
