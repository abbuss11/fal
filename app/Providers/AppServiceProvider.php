<?php

namespace App\Providers;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Project;
use App\Observers\ProjectObserver;
use App\Observers\TaskCommentObserver;
use App\Observers\TaskObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->clearStaleViteHotFile();

        Project::observe(ProjectObserver::class);
        Task::observe(TaskObserver::class);
        TaskComment::observe(TaskCommentObserver::class);
    }

    private function clearStaleViteHotFile(): void
    {
        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return;
        }

        $endpoint = trim((string) @file_get_contents($hotFile));
        $parsed = parse_url($endpoint);

        $host = $parsed['host'] ?? null;
        $port = isset($parsed['port']) ? (int) $parsed['port'] : null;

        if (! is_string($host) || $host === '' || ! $port) {
            @unlink($hotFile);

            return;
        }

        $connection = @fsockopen($host, $port, $errno, $errstr, 0.15);

        if ($connection === false) {
            @unlink($hotFile);

            return;
        }

        fclose($connection);
    }
}
