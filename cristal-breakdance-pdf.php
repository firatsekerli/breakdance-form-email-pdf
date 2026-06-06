<?php
/**
 * Plugin Name:       Cristal Breakdance Form PDF
 * Plugin URI:        https://github.com/firatsekerli/breakdance-form-email-pdf
 * Description:        Adds a standalone "Send PDF" action to Breakdance forms. On submission it builds a branded PDF containing only the fields relevant to the selected Request Reason and emails it (short summary in the body, full details attached as a PDF, plus any uploaded files).
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Author:            Cristal Windows
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cristal-breakdance-pdf
 *
 * This plugin is intentionally standalone: it does NOT modify or replace the
 * existing "Conditional Email" action (which is provided by a different
 * plugin). Add "Send PDF" alongside your other Actions After Submission.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CRISTAL_BD_PDF_VERSION', '1.0.0');
define('CRISTAL_BD_PDF_DIR', plugin_dir_path(__FILE__));
define('CRISTAL_BD_PDF_URL', plugin_dir_url(__FILE__));

// Composer autoloader (mPDF + plugin classes).
$cristal_bd_pdf_autoload = CRISTAL_BD_PDF_DIR . 'vendor/autoload.php';
if (file_exists($cristal_bd_pdf_autoload)) {
    require_once $cristal_bd_pdf_autoload;
}

/**
 * Register the custom Breakdance form action.
 *
 * Registration must happen on `init` so the Breakdance Forms API is loaded,
 * per the Form Actions API documentation.
 */
add_action('init', function () {
    if (!function_exists('\\Breakdance\\Forms\\Actions\\registerAction')) {
        // Breakdance (or its Forms API) is not active; nothing to register.
        return;
    }

    if (!class_exists('\\CristalWindows\\BreakdanceFormPdf\\SendPdfAction')) {
        // Autoloader missing or `composer install` not run.
        return;
    }

    \Breakdance\Forms\Actions\registerAction(
        new \CristalWindows\BreakdanceFormPdf\SendPdfAction()
    );
});

/**
 * Admin notice if the mPDF dependency is missing (composer install not run).
 */
add_action('admin_notices', function () {
    if (class_exists('\\Mpdf\\Mpdf')) {
        return;
    }
    echo '<div class="notice notice-error"><p><strong>Cristal Breakdance Form PDF:</strong> '
        . 'the mPDF library is missing. Run <code>composer install</code> inside the plugin '
        . 'directory before activating, or upload a build that includes the <code>vendor/</code> folder.</p></div>';
});
