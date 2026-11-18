<?php

namespace App\Support;

/**
 * Builds the array that resources/views/components/toast-stack.blade.php renders.
 *
 *   return redirect()->route('admin.news.index')
 *       ->with('toast', Toast::success('Archived "Relief"', 'It is no longer on the public portal.'));
 *
 * $action = ['label' => 'Restore', 'url' => route(...), 'method' => 'PATCH']  (method defaults to GET)
 */
final class Toast
{
    public static function success(string $title, ?string $message = null, ?array $action = null): array
    {
        return self::make('success', $title, $message, $action);
    }

    public static function error(string $title, ?string $message = null, ?array $action = null): array
    {
        return self::make('error', $title, $message, $action);
    }

    public static function warning(string $title, ?string $message = null, ?array $action = null): array
    {
        return self::make('warning', $title, $message, $action);
    }

    public static function info(string $title, ?string $message = null, ?array $action = null): array
    {
        return self::make('info', $title, $message, $action);
    }

    private static function make(string $type, string $title, ?string $message, ?array $action): array
    {
        return array_filter([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action' => $action,
        ], fn ($value) => $value !== null);
    }
}