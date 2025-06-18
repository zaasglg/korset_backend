<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateApiDocs extends Command
{
    protected $signature = 'api:docs';
    protected $description = 'Generate API documentation in PDF format';

    public function handle()
    {
        $this->info('Generating API documentation...');

        // Read the README.md file
        $markdown = File::get(base_path('README.md'));

        // Convert markdown to HTML
        $html = $this->convertMarkdownToHtml($markdown);

        // Generate PDF
        $pdf = PDF::loadHTML($html);

        // Save PDF
        $pdf->save(base_path('storage/app/public/api-docs.pdf'));

        $this->info('API documentation generated successfully!');
        $this->info('PDF file saved at: ' . base_path('storage/app/public/api-docs.pdf'));
    }

    private function convertMarkdownToHtml($markdown)
    {
        // Basic markdown to HTML conversion
        $html = '<html><head><style>';
        $html .= '
            body { font-family: Arial, sans-serif; line-height: 1.6; margin: 40px; }
            h1 { color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; }
            h2 { color: #34495e; margin-top: 30px; }
            h3 { color: #7f8c8d; }
            code { background: #f8f9fa; padding: 2px 5px; border-radius: 3px; }
            pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
            table { border-collapse: collapse; width: 100%; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f8f9fa; }
        ';
        $html .= '</style></head><body>';

        // Convert headers
        $markdown = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $markdown);
        $markdown = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $markdown);
        $markdown = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $markdown);

        // Convert code blocks
        $markdown = preg_replace('/```(.*?)```/s', '<pre><code>$1</code></pre>', $markdown);

        // Convert inline code
        $markdown = preg_replace('/`(.*?)`/', '<code>$1</code>', $markdown);

        // Convert lists
        $markdown = preg_replace('/^- (.*$)/m', '<li>$1</li>', $markdown);
        $markdown = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $markdown);

        // Convert bold and italic
        $markdown = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $markdown);
        $markdown = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $markdown);

        $html .= $markdown;
        $html .= '</body></html>';

        return $html;
    }
}
