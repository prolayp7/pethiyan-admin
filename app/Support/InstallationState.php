<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class InstallationState
{
    public static function isInstalled(): bool
    {
        if (file_exists(storage_path('installed'))) {
            return true;
        }

        return self::hasTable('migrations');
    }

    public static function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
