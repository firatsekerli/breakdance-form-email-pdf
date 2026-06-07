<?php
/**
 * PDF HTML template (rendered by mPDF).
 *
 * Available variables (set in PdfBuilder::buildHtml):
 *
 * @var string $logo        Logo image URL (or local path).
 * @var string $brand       Brand primary colour (hex).
 * @var string $title       Document heading.
 * @var string $content     Pre-rendered inner HTML (field tokens already resolved).
 * @var string $generatedAt Human-readable generation timestamp.
 */

if (!defined('ABSPATH')) {
    exit;
}

$brandColour = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');
$logoUrl     = htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');
$titleText   = htmlspecialchars($title !== '' ? $title : 'Contact Form Submission', ENT_QUOTES, 'UTF-8');
$generated   = htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $titleText; ?> - Cristal Windows</title>
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
        .header-logo { width: 130px; vertical-align: bottom; }
        .header-logo img { width: 130px; height: auto; }
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
        .doc-meta { font-size: 11px; color: #777; margin-bottom: 18px; }
        .doc-content { color: #222; }
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

    <h1 class="doc-title"><?php echo $titleText; ?></h1>
    <div class="doc-meta">Generated <?php echo $generated; ?></div>

    <div class="doc-content">
        <?php echo $content; // resolved field content from the action layer ?>
    </div>

    <div class="footer">
        Cristal Windows, Doors &amp; Conservatories Ltd &middot; This document was generated automatically from the website contact form.
    </div>
</body>
</html>
