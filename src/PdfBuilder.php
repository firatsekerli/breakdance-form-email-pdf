<?php

namespace CristalWindows\BreakdanceFormPdf;

use Mpdf\Mpdf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wraps rendered content HTML in a branded shell and produces a PDF (mPDF).
 *
 * Branding (colours, logo, contact strip) mirrors the existing Cristal
 * "quotation-form" plugin so the look is consistent across documents.
 */
class PdfBuilder
{
    /** Primary brand colour (Cristal blue). */
    const BRAND_PRIMARY = '#1a5490';

    /** Default (live) logo URL, overridable via the `cristal_bd_pdf_logo` filter. */
    const DEFAULT_LOGO = 'https://cristalwindows.co.uk/wp-content/uploads/2025/02/Cristal-Windows-LOGO-01.png';

    /**
     * Render a PDF for the given title + content HTML and return its file path.
     *
     * @param string $title       Heading shown at the top of the document.
     * @param string $contentHtml Already-rendered inner HTML (field tokens resolved).
     * @return string Absolute path to the written PDF file.
     *
     * @throws \Mpdf\MpdfException When PDF generation fails.
     */
    public function render(string $title, string $contentHtml): string
    {
        $html = $this->buildHtml($title, $contentHtml);

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 16,
            'margin_bottom' => 16,
            'tempDir'       => $this->tempDir(),
        ]);

        $mpdf->SetTitle($title !== '' ? $title : 'Contact Form Submission');
        $mpdf->SetCreator('Cristal Windows Contact Form');
        $mpdf->showImageErrors = false; // allow the remote logo to fail gracefully

        $mpdf->WriteHTML($html);

        $path = $this->outputPath($title);
        $mpdf->Output($path, \Mpdf\Output\Destination::FILE);

        return $path;
    }

    /**
     * Remove a generated PDF and its unique containing directory after sending.
     */
    public function cleanup(string $path): void
    {
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
        $dir = dirname($path);
        if (is_dir($dir) && strpos(basename($dir), 'cristal-pdf-') === 0) {
            @rmdir($dir);
        }
    }

    /**
     * Wrap the content in the branded HTML shell.
     */
    private function buildHtml(string $title, string $contentHtml): string
    {
        $logo        = apply_filters('cristal_bd_pdf_logo', self::DEFAULT_LOGO);
        $brand       = apply_filters('cristal_bd_pdf_brand_colour', self::BRAND_PRIMARY);
        $generatedAt = function_exists('wp_date') ? wp_date('j F Y, g:i a') : date('j F Y, g:i a');

        $content = $contentHtml; // resolved + sanitised by the action layer

        ob_start();
        include CRISTAL_BD_PDF_DIR . 'templates/pdf.php';
        return (string) ob_get_clean();
    }

    /**
     * Resolve (and create) a writable temp directory for mPDF's own scratch files.
     */
    private function tempDir(): string
    {
        $base = function_exists('get_temp_dir') ? get_temp_dir() : sys_get_temp_dir();
        $dir  = rtrim($base, '/\\') . '/mpdf-cristal';
        if (!is_dir($dir)) {
            if (function_exists('wp_mkdir_p')) {
                wp_mkdir_p($dir);
            } else {
                @mkdir($dir, 0775, true);
            }
        }
        return $dir;
    }

    /**
     * Build a friendly output path inside a unique temp subdirectory.
     *
     * The file's basename becomes the email attachment name, so it is given a
     * human-readable filename. A unique per-request directory avoids collisions
     * and makes cleanup trivial.
     */
    private function outputPath(string $title): string
    {
        $base   = function_exists('get_temp_dir') ? get_temp_dir() : sys_get_temp_dir();
        $unique = 'cristal-pdf-' . date('Ymd-His') . '-' . substr(md5(uniqid('', true)), 0, 8);
        $dir    = rtrim($base, '/\\') . '/' . $unique;
        if (function_exists('wp_mkdir_p')) {
            wp_mkdir_p($dir);
        } else {
            @mkdir($dir, 0775, true);
        }
        return $dir . '/' . self::attachmentFilename($title);
    }

    /**
     * Customer-facing attachment filename derived from the document title.
     */
    public static function attachmentFilename(string $title): string
    {
        $title = trim($title) !== '' ? trim($title) : 'Contact Form';
        $title = preg_replace('/[^A-Za-z0-9 \-]/', '', $title) ?? $title;
        $title = trim($title) !== '' ? trim($title) : 'Contact Form';
        return $title . ' - ' . date('Y-m-d') . '.pdf';
    }
}
