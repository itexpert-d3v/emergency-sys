<?php

return [
    'tile_url' => env('MAP_TILE_URL', env('APP_ENV') === 'production'
        ? 'https://api.maptiler.com/maps/streets-v4/{z}/{x}/{y}.png?key={key}'
        : 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
    'tile_attribution' => env('MAP_TILE_ATTRIBUTION', env('APP_ENV') === 'production'
        ? '&copy; <a href="https://www.maptiler.com/copyright/">MapTiler</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
        : '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'),
    'tile_key' => env('MAP_TILE_KEY'),
    'max_zoom' => (int) env('MAP_TILE_MAX_ZOOM', 19),
];
