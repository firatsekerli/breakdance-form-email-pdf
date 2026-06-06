<?php

namespace CristalWindows\BreakdanceFormPdf;

use Mpdf\Mpdf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Builds a branded PDF for a contact-form submission using mPDF.
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
     * Render a PDF for the given submission and return the absolute file path.
     *
     * @param string                $reason  The selected Request Reason.
     * @param array<string,string>  $values  Field id => display value (already ordered/filtered by caller).
     * @param array<string,string>  $labels  Field id => label.
     * @return string Absolute path to the written PDF file.
     *
     * @throws \Mpdf\MpdfException When PDF generation fails.
     */
    public function render(string $reason, array $values, array $labels): string
    {
        $html = $this->buildHtml($reason, $values, $labels);

        $tempDir = $this->tempDir();

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 16,
            'margin_bottom' => 16,
            'tempDir'       => $tempDir,
        ]);

        $mpdf->SetTitle('Contact Form Submission - Cristal Windows');
        $mpdf->SetCreator('Cristal Windows Contact Form');

        // Allow mPDF to fetch the remote logo from the live site.
        $mpdf->showImageErrors = false;

        $mpdf->WriteHTML($html);

        $path = $this->outputPath($reason);
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
     * Build the full HTML document for the PDF from the template.
     *
     * @param array<string,string> $values
     * @param array<string,string> $labels
     */
    private function buildHtml(string $reason, array $values, array $labels): string
    {
        $logo        = apply_filters('cristal_bd_pdf_logo', self::DEFAULT_LOGO);
        $brand       = apply_filters('cristal_bd_pdf_brand_colour', self::BRAND_PRIMARY);
        $generatedAt = function_exists('wp_date')
            ? wp_date('j F Y, g:i a')
            : date('j F Y, g:i a');

        $rows = $this->buildRows($values, $labels);

        ob_start();
        include CRISTAL_BD_PDF_DIR . 'templates/pdf.php';
        return (string) ob_get_clean();
    }

    /**
     * Build the HTML for the field rows.
     *
     * @param array<string,string> $values
     * @param array<string,string> $labels
     */
    private function buildRows(array $values, array $labels): string
    {
        $out = '';
        foreach ($values as $fieldId => $value) {
            $label    = $labels[$fieldId] ?? $fieldId;
            $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

            // Preserve line breaks for the long "Message" field.
            $safeValue = nl2br(htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
            if ($safeValue === '') {
                $safeValue = '&mdash;';
            }

            $out .= '<tr>'
                . '<td class="field-label">' . $safeLabel . '</td>'
                . '<td class="field-value">' . $safeValue . '</td>'
                . '</tr>';
        }
        return $out;
    }

    /**
     * Resolve (and create) a writable temp directory for mPDF.
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
     * Build a meaningful output file path inside a unique temp subdirectory.
     *
     * The file's basename becomes the attachment name shown in the email, so it
     * is given a friendly, human-readable filename. A unique per-request
     * directory keeps concurrent submissions from colliding and makes cleanup
     * trivial.
     */
    private function outputPath(string $reason): string
    {
        $base = function_exists('get_temp_dir') ? get_temp_dir() : sys_get_temp_dir();
        $unique = 'cristal-pdf-' . date('Ymd-His') . '-' . substr(md5(uniqid('', true)), 0, 8);
        $dir = rtrim($base, '/\\') . '/' . $unique;
        if (function_exists('wp_mkdir_p')) {
            wp_mkdir_p($dir);
        } else {
            @mkdir($dir, 0775, true);
        }
        return $dir . '/' . self::attachmentFilename($reason);
    }

    /**
     * Public helper: the customer-facing attachment filename for an email.
     */
    public static function attachmentFilename(string $reason): string
    {
        $reason = $reason !== '' ? $reason : 'Contact Form';
        // Keep characters that are safe and readable across mail clients.
        $reason = preg_replace('/[^A-Za-z0-9 \-]/', '', $reason) ?? $reason;
        return 'Contact Form - ' . trim($reason) . ' - ' . date('Y-m-d') . '.pdf';
    }
}
