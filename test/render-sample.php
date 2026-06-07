<?php
/**
 * Standalone test harness — renders a sample PDF from rich-text content
 * (as it would be configured in the Breakdance UI) WITHOUT a running
 * WordPress install. Verifies mPDF output and the branded template.
 *
 * Usage:  php test/render-sample.php  [output-dir]
 */

error_reporting(E_ALL & ~E_DEPRECATED);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

// --- Minimal WordPress stubs ------------------------------------------------
if (!defined('ABSPATH')) {
    define('ABSPATH', $root . '/');
}
if (!defined('BD_FORM_PDF_DIR')) {
    define('BD_FORM_PDF_DIR', $root . '/');
}
if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args) { return $value; }
}
if (!function_exists('wp_date')) {
    function wp_date($format) { return date($format); }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo($key = 'name') { return 'Example Site'; }
}
if (!function_exists('home_url')) {
    function home_url() { return 'https://example.com'; }
}
if (!function_exists('get_temp_dir')) {
    function get_temp_dir() { return rtrim(sys_get_temp_dir(), '/\\') . '/'; }
}
if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($dir) { return is_dir($dir) || mkdir($dir, 0775, true); }
}

require $root . '/src/PdfBuilder.php';

use BreakdanceFormPdf\PdfBuilder;

// --- Sample rendered content (what the action produces after token replace) -
$samples = [
    'Service Request' => <<<HTML
<div style="font-size:18px;"><strong>Request Reason:</strong> Service Request</div>
<div style="font-size:18px;"><strong>Full Name:</strong> Jane Doe</div>
<div style="font-size:18px;"><strong>Email:</strong> jane.doe@example.com</div>
<div style="font-size:18px;"><strong>Phone Number:</strong> 555 0123</div>
<div style="font-size:18px;"><strong>Message:</strong> The handle on the rear door has come loose.</div>
HTML,
    'Quotation Request' => <<<HTML
<div style="font-size:18px;"><strong>Request Reason:</strong> Quotation Request</div>
<div style="font-size:18px;"><strong>Full Name:</strong> Jane Doe</div>
<div style="font-size:18px;"><strong>Email:</strong> jane.doe@example.com</div>
<div style="font-size:18px;"><strong>Phone Number:</strong> 555 0123</div>
<div style="font-size:18px;"><strong>Message:</strong> I'd like a quote for a replacement window.</div>
HTML,
];

$outDir = $argv[1] ?? (rtrim(sys_get_temp_dir(), '/\\') . '/bd-form-pdf-samples');
if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

$builder = new PdfBuilder();

// Optional branding (as it would come from the UI's "PDF Appearance" section).
$branding = [
    'logo'   => '',           // no logo -> site name shown instead
    'colour' => '#2271b1',
    'footer' => 'Example Site',
];

foreach ($samples as $title => $content) {
    $path = $builder->render($title, $content, $branding);

    $dest = $outDir . '/' . basename($path);
    rename($path, $dest);
    @rmdir(dirname($path));

    printf("Rendered: %s (%s bytes)\n", $dest, filesize($dest));
}

echo "\nDone. Sample PDFs written to: {$outDir}\n";
