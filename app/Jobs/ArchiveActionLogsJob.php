<?php

namespace App\Jobs;

use App\Models\ActionLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class ArchiveActionLogsJob implements ShouldQueue
{
    use Queueable;
    
    public int $timeout = 3600;
    public int $tries = 3;
    
    public function __construct(
        public string $archiveBeforeIso
    ) {}
    
    public function handle(): void
    {
        $archiveBefore = Carbon::parse($this->archiveBeforeIso);
        $directory = storage_path(config("app.logs_directory"));
        
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        ActionLog::query()
            ->where('created_at', '<', $archiveBefore)
            ->orderBy('id')
            ->chunkById(500, function (Collection $logs) use ($directory) {
                $groupedLogs = $logs->groupBy(function (ActionLog $log) {
                    return Carbon::createFromTimestamp($log->created_at)->format('Y_m_d');
                });
                
                foreach ($groupedLogs as $day => $dayLogs) {
                    $filepath = $directory . DIRECTORY_SEPARATOR . 'action_logs_' . $day . '.log';
                    
                    $content = $dayLogs->map(function (ActionLog $log) {
                            return sprintf(
                                '%s - %s [%s] "%s %s HTTP/1.1" %s - "%s" "%s" [%s] action="%s" target="%s:%s" description="%s"',
                                $log->ip_address ?? '-',
                                $log->user_id ?? '-',
                                Carbon::createFromTimestamp($log->created_at)->format('d/M/Y:H:i:s O'),
                                $log->method ?? '-',
                                $log->url ?? '-',
                                strtoupper($log->severity ?? 'INFO'),
                                $log->user_role ?? '-',
                                $log->user_agent ?? '-',
                                $log->id,
                                $log->action ?? '-',
                                $log->target_type ?? '-',
                                $log->target_id ?? '-',
                                str_replace('"', "'", $log->description ?? '-')
                            );
                        })->implode(PHP_EOL) . PHP_EOL;
                    
                    File::append($filepath, $content);
                    
                    if (File::size($filepath) > 50 * 1024 * 1024) {
                        $gzFilepath = $filepath . '.gz';
                        
                        $source = fopen($filepath, 'rb');
                        $target = gzopen($gzFilepath, 'ab9');
                        
                        if ($source === false || $target === false) {
                            throw new \RuntimeException("Impossible de compresser le fichier d'archive: {$filepath}");
                        }
                        
                        while (!feof($source)) {
                            $chunk = fread($source, 1024 * 512);
                            
                            if ($chunk !== false && $chunk !== '') {
                                gzwrite($target, $chunk);
                            }
                        }
                        
                        fclose($source);
                        gzclose($target);
                        
                        File::delete($filepath);
                    }
                }
                
                ActionLog::query()
                    ->whereIn('id', $logs->pluck('id'))
                    ->delete();
            });
    }
}
