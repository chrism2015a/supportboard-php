<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class AutoInstaller
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip installer check for installer routes and storage/assets
        $skipPaths = [
            '/api/installer',
            '/installer',
            '/install',
            '/storage/',
            '/vendor/',
            '/build/',
        ];

        foreach ($skipPaths as $path) {
            if (str_starts_with($request->path(), ltrim($path, '/'))) {
                return $next($request);
            }
        }

        // Check if system is properly configured
        if ($this->isSystemUnconfigured()) {
            // Redirect to installer
            return redirect()->route('installer.index')
                ->with('info', 'System not configured. Please complete the setup wizard.');
        }

        return $next($request);
    }

    /**
     * Check if system needs configuration.
     */
    protected function isSystemUnconfigured(): bool
    {
        // Check 1: .env file exists
        if (!file_exists(base_path('.env'))) {
            return true;
        }

        // Check 2: Required env variables exist
        $requiredVars = ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'APP_KEY'];
        foreach ($requiredVars as $var) {
            if (empty(env($var))) {
                return true;
            }
        }

        // Check 3: Database connection works
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            return true;
        }

        // Check 4: Required tables exist
        $requiredTables = ['users', 'departments', 'tickets', 'conversations'];
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                return true;
            }
        }

        return false;
    }
}
