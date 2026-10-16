<?php

namespace App\Support;

use Inertia\Inertia;

class Toast
{
    /**
     * Flash a toast message to the session for Inertia.
     */
    public static function show(string $type, string $message): void
    {
        Inertia::flash('toast', [
            'type' => $type,
            'message' => $message,
        ]);
    }

    /**
     * Flash a success toast message.
     */
    public static function success(string $message): void
    {
        self::show('success', $message);
    }

    /**
     * Flash an error toast message.
     */
    public static function error(string $message): void
    {
        self::show('error', $message);
    }

    /**
     * Flash an info toast message.
     */
    public static function info(string $message): void
    {
        self::show('info', $message);
    }

    /**
     * Flash a warning toast message.
     */
    public static function warning(string $message): void
    {
        self::show('warning', $message);
    }
}
