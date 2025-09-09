<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Video Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure video upload settings for your application.
    |
    */

    'max_file_size' => env('VIDEO_MAX_FILE_SIZE', 104857600), // 100MB in bytes

    'allowed_mime_types' => [
        'video/mp4',
        'video/avi', 
        'video/x-msvideo',
        'video/quicktime',
        'video/x-ms-wmv',
        'video/x-flv',
        'video/webm',
        'video/x-matroska'
    ],

    'allowed_extensions' => [
        'mp4',
        'avi',
        'mov',
        'wmv',
        'flv',
        'webm',
        'mkv'
    ],

    'storage_disk' => env('VIDEO_STORAGE_DISK', 'public'),

    'storage_path' => env('VIDEO_STORAGE_PATH', 'videos'),

    'generate_thumbnails' => env('VIDEO_GENERATE_THUMBNAILS', true),

    'thumbnail_path' => env('VIDEO_THUMBNAIL_PATH', 'thumbnails'),

    // Video optimization settings
    'optimization' => [
        'enabled' => env('VIDEO_OPTIMIZATION_ENABLED', true),
        'max_width' => env('VIDEO_MAX_WIDTH', 1280),
        'max_height' => env('VIDEO_MAX_HEIGHT', 720),
        'quality' => env('VIDEO_QUALITY', 28), // CRF value (lower = better quality)
        'preset' => env('VIDEO_PRESET', 'medium'), // FFmpeg preset
        'audio_bitrate' => env('VIDEO_AUDIO_BITRATE', '128k'),
    ],

    // FFmpeg settings
    'ffmpeg' => [
        'enabled' => env('FFMPEG_ENABLED', true),
        'path' => env('FFMPEG_PATH', '/opt/homebrew/bin/ffmpeg'),
        'ffprobe_path' => env('FFPROBE_PATH', '/opt/homebrew/bin/ffprobe'),
    ],

    'cleanup_on_delete' => env('VIDEO_CLEANUP_ON_DELETE', true),

];
