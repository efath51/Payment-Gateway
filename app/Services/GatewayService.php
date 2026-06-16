<?php

namespace App\Services;

use InvalidArgumentException;

// Import all gateway classes
use App\Gateways\BkashGateway;
use App\Gateways\SslcommerzGateway;
use App\Gateways\ManualGateway;

class GatewayService
{
    /**
     * The gateway registry - map alias to class
     * This is the ONLY place you add a new gateway
     */
    private static array $gateways = [
        'bkash'      => BkashGateway::class,
        'sslcommerz' => SslcommerzGateway::class,
        'manual'     => ManualGateway::class,
    ];

    /**
     * Create and return gateway object
     * Usage: GatewayService::make('bkash')
     */
    public static function make(string $alias)
    {
        if (!isset(self::$gateways[$alias])) {
            throw new InvalidArgumentException(
                "Gateway '{$alias}' not found. Available: " . implode(', ', array_keys(self::$gateways))
            );
        }

        $class = self::$gateways[$alias];
        return new $class();
    }

    /**
     * Check if gateway exists
     */
    public static function has(string $alias): bool
    {
        return isset(self::$gateways[$alias]);
    }

    /**
     * Get all available gateways
     */
    public static function all(): array
    {
        return array_keys(self::$gateways);
    }
}
