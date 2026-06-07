<?php
/**
 * Plugin Name:       Cristal Breakdance Form PDF
 * Plugin URI:        https://github.com/firatsekerli/breakdance-form-email-pdf
 * Description:        Adds a standalone "Send PDF" action to Breakdance forms. Conditional rules choose the recipient and the rich-text content, which is rendered to a branded PDF and emailed (short summary in the body, full details attached, plus any uploaded files).
 * Version:           2.0.0
 * Requires PHP:      7.4
 * Requires Plugins:  breakdance
 * Author:            Cristal Windows
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cristal-breakdance-pdf
 *
 * Standalone: this plugin does NOT modify or replace the separate
 * "Conditional Email" action (provided by a different plugin). Add "Send PDF"
 * alongside your other Actions After Submission.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CRISTAL_BD_PDF_VERSION', '2.0.0');
define('CRISTAL_BD_PDF_DIR', plugin_dir_path(__FILE__));
define('CRISTAL_BD_PDF_URL', plugin_dir_url(__FILE__));

// Composer autoloader (mPDF + plugin classes).
$cristal_bd_pdf_autoload = CRISTAL_BD_PDF_DIR . 'vendor/autoload.php';
if (file_exists($cristal_bd_pdf_autoload)) {
    require_once $cristal_bd_pdf_autoload;
}

/**
 * Register the "Send PDF" action once Breakdance (and its Forms API) is loaded.
 *
 * Mirrors the production registration pattern: wait for `breakdance_loaded`,
 * verify the base Action class, then register via the available API with
 * fallbacks for older/newer Breakdance versions.
 */
add_action('breakdance_loaded', function () {
    if (!class_exists('\\Breakdance\\Forms\\Actions\\Action')) {
        return;
    }

    $actionClass = '\\CristalWindows\\BreakdanceFormPdf\\Actions\\SendPdf';
    if (!class_exists($actionClass)) {
        // Autoloader missing or `composer install` not run.
        return;
    }

    if (function_exists('\\Breakdance\\Forms\\Actions\\registerAction')) {
        \Breakdance\Forms\Actions\registerAction(new $actionClass());
        return;
    }

    if (function_exists('\\Breakdance\\Forms\\registerFormActionClass')) {
        \Breakdance\Forms\registerFormActionClass($actionClass);
        return;
    }

    add_filter('breakdance_forms_actions', function ($actions) use ($actionClass) {
        if (is_array($actions)) {
            $actions[] = $actionClass;
        }
        return $actions;
    });
}, 50);

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
