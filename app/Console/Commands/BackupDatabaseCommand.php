<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--keep=14 : Number of daily backups to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a compressed database backup and rotate old files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');

        if (empty($database) || empty($username)) {
            $this->error('Database configuration is incomplete.');
            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$database}_{$timestamp}.sql";
        $filePath = "{$backupDir}/{$filename}";
        $gzPath = "{$filePath}.gz";

        $this->info("⏳ Creating backup of database [{$database}]...");

        // Detect mysqldump path
        $mysqldump = 'mysqldump';
        if (PHP_OS_FAMILY === 'Windows') {
            $windowsPath = 'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe';
            if (file_exists($windowsPath)) {
                $mysqldump = "\"{$windowsPath}\"";
            }
        }

        // Build command with safe credentials
        $command = sprintf(
            '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --quick --routines --triggers %s',
            $mysqldump,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database)
        );

        // Execute dump
        $process = Process::fromShellCommandLine("{$command} > " . escapeshellarg($filePath));
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful() || !file_exists($filePath) || filesize($filePath) === 0) {
            $this->error('Failed to create database dump: ' . $process->getErrorOutput());
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            return self::FAILURE;
        }

        // Compress with gzip if available
        $finalPath = $filePath;
        $gzProcess = Process::fromShellCommandLine("gzip -f " . escapeshellarg($filePath));
        $gzProcess->run();

        if (file_exists($gzPath)) {
            $finalPath = $gzPath;
        }

        $size = number_format(filesize($finalPath) / 1024, 2);
        $this->info("✅ Database backup created: {$finalPath} ({$size} KB)");

        // Prune old backups (default 14 days)
        $keepDays = (int) $this->option('keep');
        $this->pruneOldBackups($backupDir, $keepDays);

        return self::SUCCESS;
    }

    /**
     * Remove backups older than specified days.
     */
    protected function pruneOldBackups(string $dir, int $days): void
    {
        $cutoff = now()->subDays($days)->getTimestamp();
        $files = File::files($dir);

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $this->line("🗑️ Pruned old backup: {$file->getFilename()}");
            }
        }
    }
}
