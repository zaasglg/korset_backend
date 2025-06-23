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

    'generate_thumbnails' => env('VIDEO_GENERATE_THUMBNAILS', false),

    'thumbnail_path' => env('VIDEO_THUMBNAIL_PATH', 'thumbnails'),

    // FFmpeg settings (optional)
    'ffmpeg' => [
        'enabled' => env('FFMPEG_ENABLED', false),
        'path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
        'ffprobe_path' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),
    ],

    'cleanup_on_delete' => env('VIDEO_CLEANUP_ON_DELETE', true),

];
