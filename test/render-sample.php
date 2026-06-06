<?php
/**
 * Standalone test harness — renders a sample PDF for each request reason
 * WITHOUT a running WordPress install. Useful for verifying mPDF output and
 * the branded template during development.
 *
 * Usage:  php test/render-sample.php  [output-dir]
 *
 * It stubs the handful of WordPress functions the PDF code touches.
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
    function apply_filters($tag, $value, ...$args)
    {
        return $value;
    }
}
if (!function_exists('wp_date')) {
    function wp_date($format)
    {
        return date($format);
    }
}
if (!function_exists('get_temp_dir')) {
    function get_temp_dir()
    {
        return rtrim(sys_get_temp_dir(), '/\\') . '/';
    }
}
if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($dir)
    {
        return is_dir($dir) || mkdir($dir, 0775, true);
    }
}

require $root . '/src/FieldConfig.php';
require $root . '/src/PdfBuilder.php';

use CristalWindows\BreakdanceFormPdf\FieldConfig;
use CristalWindows\BreakdanceFormPdf\PdfBuilder;

// --- Sample submission data -------------------------------------------------
$sample = [
    'request_reason'    => '', // set per reason below
    'installation_date' => '2024-03-18',
    'contract_number'   => 'CN-20481',
    'FIRSTSURNAME'      => 'Jane Hartley',
    'email'             => 'jane.hartley@example.com',
    'alt_email'         => 'j.hartley.work@example.com',
    'MOBTEL'            => '07700 900123',
    'alt_phone'         => '01252 900456',
    'HOUSENO'           => '42',
    'STREET'            => 'Maple Avenue',
    'TOWN'             => 'Farnborough',
    'COUNTY'           => 'Hampshire',
    'PCODE'            => 'GU14 6AA',
    'SUBSOURCE'        => 'Replacement bay window quote',
    'NOTES'            => "Hello,\n\nI'd like a quote for a replacement bay window at the front of the property.\nPlease call after 5pm.\n\nThanks,\nJane",
];

$outDir = $argv[1] ?? (rtrim(sys_get_temp_dir(), '/\\') . '/cristal-pdf-samples');
if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

$reasons = [
    FieldConfig::REASON_GENERAL,
    FieldConfig::REASON_SERVICE,
    FieldConfig::REASON_QUOTE,
];

$builder = new PdfBuilder();

foreach ($reasons as $reason) {
    $fields = $sample;
    $fields['request_reason'] = $reason;

    $order  = FieldConfig::orderForReason($reason);
    $labels = [];
    $values = [];
    foreach ($order as $id) {
        $labels[$id] = FieldConfig::label($id);
        $value = $fields[$id] ?? '';
        if ($id === 'installation_date' && $value !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', $value);
            if ($dt) {
                $value = $dt->format('j F Y');
            }
        }
        $values[$id] = $value;
    }

    $path = $builder->render($reason, $values, $labels);

    // Move into the requested output dir with the friendly name.
    $dest = $outDir . '/' . basename($path);
    rename($path, $dest);
    @rmdir(dirname($path));

    printf("Rendered: %s (%d fields, %s)\n", $dest, count($values), filesize($dest) . ' bytes');
}

echo "\nDone. Sample PDFs written to: {$outDir}\n";
