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
if (!defined('CRISTAL_BD_PDF_DIR')) {
    define('CRISTAL_BD_PDF_DIR', $root . '/');
}
if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args) { return $value; }
}
if (!function_exists('wp_date')) {
    function wp_date($format) { return date($format); }
}
if (!function_exists('get_temp_dir')) {
    function get_temp_dir() { return rtrim(sys_get_temp_dir(), '/\\') . '/'; }
}
if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($dir) { return is_dir($dir) || mkdir($dir, 0775, true); }
}

require $root . '/src/PdfBuilder.php';

use CristalWindows\BreakdanceFormPdf\PdfBuilder;

// --- Sample rendered content (what the action produces after token replace) -
$samples = [
    'Service Call Request' => <<<HTML
<div style="font-size:18px;"><strong>Request Reason:</strong> Service Call Request</div>
<div style="font-size:18px;"><strong>Approximate Date of Installation:</strong> 18 March 2024</div>
<div style="font-size:18px;"><strong>Contract Number:</strong> CN-20481</div>
<div style="font-size:18px;"><strong>Full Name:</strong> Jane Hartley</div>
<div style="font-size:18px;"><strong>Email:</strong> jane.hartley@example.com</div>
<div style="font-size:18px;"><strong>Phone Number:</strong> 07700 900123</div>
<div style="font-size:18px;"><strong>Postcode:</strong> GU14 6AA</div>
<div style="font-size:18px;"><strong>Message:</strong> The handle on the rear door has come loose.</div>
HTML,
    'Quotation Request' => <<<HTML
<div style="font-size:18px;"><strong>Request Reason:</strong> Quotation Request</div>
<div style="font-size:18px;"><strong>Full Name:</strong> Jane Hartley</div>
<div style="font-size:18px;"><strong>Email:</strong> jane.hartley@example.com</div>
<div style="font-size:18px;"><strong>Phone Number:</strong> 07700 900123</div>
<div style="font-size:18px;"><strong>Postcode:</strong> GU14 6AA</div>
<div style="font-size:18px;"><strong>Message:</strong> I'd like a quote for a replacement bay window.</div>
HTML,
];

$outDir = $argv[1] ?? (rtrim(sys_get_temp_dir(), '/\\') . '/cristal-pdf-samples');
if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

$builder = new PdfBuilder();

foreach ($samples as $title => $content) {
    $path = $builder->render($title, $content);

    $dest = $outDir . '/' . basename($path);
    rename($path, $dest);
    @rmdir(dirname($path));

    printf("Rendered: %s (%s bytes)\n", $dest, filesize($dest));
}

echo "\nDone. Sample PDFs written to: {$outDir}\n";
