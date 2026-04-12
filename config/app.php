<?php

declare(strict_types=1);

return [
    'cache_dir' => __DIR__ . '/../cache',
    'cache_ttl_seconds' => 900,
    'items_per_page' => 9,
    'max_games_to_process' => 100, // Safety limit for API calls
];
