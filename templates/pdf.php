<?php
/**
 * PDF HTML template (rendered by mPDF).
 *
 * Available variables (set in PdfBuilder::buildHtml):
 *
 * @var string $logo        Logo image URL (or local path).
 * @var string $brand       Brand primary colour (hex).
 * @var string $reason      Selected request reason.
 * @var string $rows        Pre-built <tr> rows for the field table.
 * @var string $generatedAt Human-readable generation timestamp.
 */

if (!defined('ABSPATH')) {
    exit;
}

$brandColour = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');
$logoUrl     = htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');
$reasonText  = htmlspecialchars($reason !== '' ? $reason : 'Contact Form Submission', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Contact Form Submission - Cristal Windows</title>
    <style>
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            color: #333;
            font-size: 12px;
            line-height: 1.5;
        }

        .header {
            border-bottom: 3px solid <?php echo $brandColour; ?>;
            padding-bottom: 12px;
            margin-bottom: 20px;
            width: 100%;
        }

        .header-logo {
            width: 130px;
            vertical-align: bottom;
        }

        .header-logo img {
            width: 130px;
            height: auto;
        }

        .header-contact {
            text-align: right;
            font-size: 11px;
            color: #333;
            line-height: 1.7;
            vertical-align: bottom;
        }

        h1.doc-title {
            color: <?php echo $brandColour; ?>;
            font-size: 18px;
            margin: 0 0 4px 0;
        }

        .doc-meta {
            font-size: 11px;
            color: #777;
            margin-bottom: 18px;
        }

        table.fields {
            width: 100%;
            border-collapse: collapse;
        }

        table.fields td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e6ea;
            vertical-align: top;
        }

        td.field-label {
            width: 38%;
            font-weight: bold;
            color: #555;
            background: #f8f9fa;
        }

        td.field-value {
            color: #222;
        }

        .footer {
            margin-top: 26px;
            padding-top: 10px;
            border-top: 1px solid #e2e6ea;
            font-size: 10px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td class="header-logo">
                <img src="<?php echo $logoUrl; ?>" alt="Cristal Windows">
            </td>
            <td class="header-contact">
                www.cristalwindows.co.uk<br>
                01252 810777 | sales@cristalwindows.co.uk
            </td>
        </tr>
    </table>

    <h1 class="doc-title"><?php echo $reasonText; ?></h1>
    <div class="doc-meta">Contact form submission &middot; Generated <?php echo htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8'); ?></div>

    <table class="fields">
        <?php echo $rows; // already escaped in PdfBuilder::buildRows() ?>
    </table>

    <div class="footer">
        Cristal Windows, Doors &amp; Conservatories Ltd &middot; This document was generated automatically from the website contact form.
    </div>
</body>
</html>
