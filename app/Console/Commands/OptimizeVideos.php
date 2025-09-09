<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\VideoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:optimize {--force : Force re-optimization of already optimized videos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize existing video files to reduce storage space';

    protected VideoService $videoService;

    public function __construct(VideoService $videoService)
    {
        parent::__construct();
        $this->videoService = $videoService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting video optimization...');
        
        $force = $this->option('force');
        
        // Get all products with videos
        $products = Product::whereNotNull('video')
            ->where('video', '!=', '')
            ->get();
            
        if ($products->isEmpty()) {
            $this->info('No videos found to optimize.');
            return;
        }
        
        $this->info("Found {$products->count()} videos to process.");
        
        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->start();
        
        $optimized = 0;
        $skipped = 0;
        $errors = 0;
        $totalSaved = 0;
        
        foreach ($products as $product) {
            try {
                $videoPath = $product->video;
                
                // Skip if already optimized (unless force flag is used)
                if (!$force && str_contains($videoPath, '_optimized')) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }
                
                // Check if video file exists
                if (!Storage::disk('public')->exists($videoPath)) {
                    $this->warn("\nVideo file not found: {$videoPath}");
                    $errors++;
                    $progressBar->advance();
                    continue;
                }
                
                $originalSize = Storage::disk('public')->size($videoPath);
                
                // Create optimized version
                $tempPath = 'temp/' . basename($videoPath);
                Storage::disk('public')->copy($videoPath, $tempPath);
                
                $optimizedPath = $this->optimizeVideoFile($tempPath, dirname($videoPath));
                
                if ($optimizedPath) {
                    $optimizedSize = Storage::disk('public')->size($optimizedPath);
                    $saved = $originalSize - $optimizedSize;
                    $totalSaved += $saved;
                    
                    // Update product with new video path
                    $product->update(['video' => $optimizedPath]);
                    
                    // Delete original file if different from optimized
                    if ($videoPath !== $optimizedPath) {
                        Storage::disk('public')->delete($videoPath);
                    }
                    
                    $optimized++;
                    
                    $this->newLine();
                    $this->info("Optimized: {$product->name}");
                    $this->info("Size: " . $this->formatBytes($originalSize) . " → " . $this->formatBytes($optimizedSize));
                    $this->info("Saved: " . $this->formatBytes($saved) . " (" . round(($saved / $originalSize) * 100, 1) . "%)");
                } else {
                    $errors++;
                }
                
                // Clean up temp file
                Storage::disk('public')->delete($tempPath);
                
            } catch (\Exception $e) {
                $this->error("\nError processing {$product->name}: " . $e->getMessage());
                $errors++;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        
        $this->newLine(2);
        $this->info('Video optimization completed!');
        $this->info("Optimized: {$optimized} videos");
        $this->info("Skipped: {$skipped} videos");
        $this->info("Errors: {$errors} videos");
        $this->info("Total space saved: " . $this->formatBytes($totalSaved));
        
        if ($totalSaved > 0) {
            $this->info("Average compression: " . round(($totalSaved / ($products->count() * 1024 * 1024)) * 100, 1) . "%");
        }
    }
    
    /**
     * Optimize a single video file
     */
    protected function optimizeVideoFile(string $inputPath, string $outputDirectory): ?string
    {
        try {
            if (!function_exists('shell_exec') || !$this->isCommandAvailable('ffmpeg')) {
                return null;
            }

            $inputFullPath = Storage::disk('public')->path($inputPath);
            $outputFileName = pathinfo($inputPath, PATHINFO_FILENAME) . '_optimized.mp4';
            $outputPath = $outputDirectory . '/' . $outputFileName;
            $outputFullPath = Storage::disk('public')->path($outputPath);
            
            // Create output directory if it doesn't exist
            $outputDir = dirname($outputFullPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // FFmpeg command for optimization
            $command = sprintf(
                'ffmpeg -i %s -c:v libx264 -preset medium -crf 28 -c:a aac -b:a 128k -movflags +faststart -vf "scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2" %s 2>/dev/null',
                escapeshellarg($inputFullPath),
                escapeshellarg($outputFullPath)
            );

            exec($command, $output, $returnCode);
            
            return $returnCode === 0 ? $outputPath : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Check if a command is available on the system
     */
    protected function isCommandAvailable(string $command): bool
    {
        if (!function_exists('shell_exec')) {
            return false;
        }

        try {
            $whereIsCommand = (PHP_OS_FAMILY === 'Windows') ? 'where' : 'which';
            $result = shell_exec("$whereIsCommand $command 2>/dev/null");
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
