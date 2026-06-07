<?php

namespace BreakdanceFormPdf;

use Mpdf\Mpdf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wraps rendered content HTML in a branded shell and produces a PDF (mPDF).
 *
 * Branding is fully configurable (logo, colour, footer) and otherwise falls
 * back to the WordPress site name/URL — no values are hardcoded.
 */
class PdfBuilder
{
    /** Neutral default brand colour (WordPress admin blue). */
    const DEFAULT_BRAND = '#2271b1';

    /**
     * Render a PDF for the given title + content HTML and return its file path.
     *
     * @param string $title       Heading shown at the top of the document.
     * @param string $contentHtml Already-rendered inner HTML (field tokens resolved).
     * @param array  $branding    Optional: 'logo', 'colour', 'footer'.
     * @return string Absolute path to the written PDF file.
     *
     * @throws \Mpdf\MpdfException When PDF generation fails.
     */
    public function render(string $title, string $contentHtml, array $branding = []): string
    {
        $b    = $this->resolveBranding($branding);
        $html = $this->buildHtml($title, $contentHtml, $b);

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 16,
            'margin_bottom' => 20,
            'tempDir'       => $this->tempDir(),
        ]);

        $mpdf->SetTitle($title !== '' ? $title : 'Form Submission');
        $mpdf->SetCreator('Breakdance Form PDF');
        $mpdf->showImageErrors = false; // allow a remote logo to fail gracefully

        // Footer pinned to the bottom margin of every page.
        if ($b['footer'] !== '') {
            $mpdf->SetHTMLFooter($this->footerHtml($b['footer']));
        }

        $mpdf->WriteHTML($html);

        $path = $this->outputPath($title);
        $mpdf->Output($path, \Mpdf\Output\Destination::FILE);

        return $path;
    }

    /**
     * Convert the configured logo into an inline data URI via a controlled
     * read/fetch, so mPDF never issues a request for an arbitrary URL or path.
     *
     * Combined with the escaped PDF content, this means mPDF makes no external
     * (remote or local) resource requests at all — closing any SSRF / local
     * file-read surface. Anything that can't be safely embedded yields '' (no
     * logo) rather than a raw URL handed to mPDF.
     */
    private function embedLogo(string $logo): string
    {
        $logo = trim($logo);
        if ($logo === '' || strncmp($logo, 'data:', 5) === 0) {
            return $logo; // empty, or already an inline data URI
        }

        $data = null;
        $mime = null;
        $max  = 2 * 1024 * 1024; // 2 MB cap

        if (@is_file($logo)) {
            // Local file path.
            $contents = @file_get_contents($logo, false, null, 0, $max + 1);
            if ($contents !== false && $contents !== '' && strlen($contents) <= $max) {
                $info = @getimagesizefromstring($contents);
                if ($info && !empty($info['mime'])) {
                    $data = $contents;
                    $mime = $info['mime'];
                }
            }
        } elseif (preg_match('#^https?://#i', $logo) && function_exists('wp_remote_get')) {
            // Remote URL — fetch in a controlled way (timeout + size + type check).
            $resp = wp_remote_get($logo, ['timeout' => 8, 'redirection' => 2]);
            if (!is_wp_error($resp) && (int) wp_remote_retrieve_response_code($resp) === 200) {
                $body  = (string) wp_remote_retrieve_body($resp);
                $ctype = (string) wp_remote_retrieve_header($resp, 'content-type');
                if ($body !== '' && strlen($body) <= $max && stripos($ctype, 'image/') === 0) {
                    $data = $body;
                    $mime = strtok($ctype, ';'); // drop any "; charset=..."
                }
            }
        }

        if ($data === null || $mime === null) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /**
     * Footer markup placed in the page's bottom margin (mPDF page footer).
     */
    private function footerHtml(string $footer): string
    {
        return '<div style="text-align:center;font-size:9px;color:#999;'
            . 'border-top:1px solid #e2e6ea;padding-top:6px;">'
            . htmlspecialchars($footer, ENT_QUOTES, 'UTF-8')
            . '</div>';
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
        if (is_dir($dir) && strpos(basename($dir), 'bd-form-pdf-') === 0) {
            @rmdir($dir);
        }
    }

    /**
     * Resolve branding values (logo, logo width, colour, footer) with filters
     * and WordPress-site fallbacks.
     *
     * @return array{logo:string,logoWidth:int,brand:string,footer:string,siteName:string}
     */
    private function resolveBranding(array $branding): array
    {
        $siteName = function_exists('get_bloginfo') ? get_bloginfo('name') : '';

        $logo      = apply_filters('bd_form_pdf_logo', $branding['logo'] ?? '');
        $logo      = $this->embedLogo((string) $logo); // controlled fetch -> data URI (or '')
        $logoWidth = (int) apply_filters('bd_form_pdf_logo_width', 120);
        if ($logoWidth < 1) {
            $logoWidth = 120;
        }

        $brand = $branding['colour'] ?? '';
        $brand = $brand !== '' ? $brand : self::DEFAULT_BRAND;
        $brand = apply_filters('bd_form_pdf_brand_colour', $brand);

        $footer = $branding['footer'] ?? '';
        $footer = $footer !== '' ? $footer : $siteName;
        $footer = (string) apply_filters('bd_form_pdf_footer', $footer, $siteName);

        return [
            'logo'      => (string) $logo,
            'logoWidth' => $logoWidth,
            'brand'     => (string) $brand,
            'footer'    => $footer,
            'siteName'  => (string) $siteName,
        ];
    }

    /**
     * Wrap the content in the branded HTML shell (header + body only; the
     * footer is rendered separately as an mPDF page footer).
     */
    private function buildHtml(string $title, string $contentHtml, array $branding): string
    {
        $logo        = $branding['logo'];
        $logoWidth   = $branding['logoWidth'];
        $brand       = $branding['brand'];
        $siteName    = $branding['siteName'];
        $generatedAt = function_exists('wp_date') ? wp_date('j F Y, g:i a') : date('j F Y, g:i a');

        $content = $contentHtml; // resolved + sanitised by the action layer

        ob_start();
        include BD_FORM_PDF_DIR . 'templates/pdf.php';
        return (string) ob_get_clean();
    }

    /**
     * Resolve (and create) a writable temp directory for mPDF's own scratch files.
     */
    private function tempDir(): string
    {
        $base = function_exists('get_temp_dir') ? get_temp_dir() : sys_get_temp_dir();
        $dir  = rtrim($base, '/\\') . '/mpdf-bd-form-pdf';
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
        $unique = 'bd-form-pdf-' . date('Ymd-His') . '-' . substr(md5(uniqid('', true)), 0, 8);
        $dir    = rtrim($base, '/\\') . '/' . $unique;
        if (function_exists('wp_mkdir_p')) {
            wp_mkdir_p($dir);
        } else {
            @mkdir($dir, 0775, true);
        }
        return $dir . '/' . self::attachmentFilename($title);
    }

    /**
     * Recipient-facing attachment filename derived from the document title.
     *
     * Unicode is preserved so all languages (Turkish, Arabic, Chinese, …) are
     * supported. Only characters that are actually illegal in file names are
     * removed: the Windows/Unix reserved set and control characters.
     */
    public static function attachmentFilename(string $title): string
    {
        $title = trim($title);
        if ($title === '') {
            $title = 'Form Submission';
        }

        // Strip filesystem-reserved characters and control chars, keep Unicode.
        $clean = preg_replace('#[\\\\/:*?"<>|\x00-\x1F]#u', '', $title);
        if (is_string($clean)) {
            $title = $clean;
        }

        // Collapse whitespace and trim leading/trailing spaces and dots.
        $collapsed = preg_replace('/\s+/u', ' ', $title);
        if (is_string($collapsed)) {
            $title = $collapsed;
        }
        $title = trim($title, " .\t\n\r\0\x0B");

        if ($title === '') {
            $title = 'Form Submission';
        }

        return $title . ' - ' . date('Y-m-d') . '.pdf';
    }
}
