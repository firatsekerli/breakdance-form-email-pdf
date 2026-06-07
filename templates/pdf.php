<?php
/**
 * PDF HTML template (rendered by mPDF).
 *
 * Available variables (set in PdfBuilder::buildHtml):
 *
 * @var string $logo        Logo image URL (or local path), may be empty.
 * @var string $brand       Brand primary colour (hex).
 * @var string $title       Document heading.
 * @var string $content     Pre-rendered inner HTML (field tokens already resolved).
 * @var string $generatedAt Human-readable generation timestamp.
 * @var string $siteName    WordPress site name.
 * @var string $siteUrl     WordPress home URL.
 * @var string $footer      Footer line (falls back to site name).
 */

if (!defined('ABSPATH')) {
    exit;
}

$brandColour = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');
$logoUrl     = htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');
$titleText   = htmlspecialchars($title !== '' ? $title : 'Form Submission', ENT_QUOTES, 'UTF-8');
$generated   = htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8');
$siteNameTxt = htmlspecialchars((string) $siteName, ENT_QUOTES, 'UTF-8');
$siteUrlTxt  = htmlspecialchars((string) $siteUrl, ENT_QUOTES, 'UTF-8');
$footerTxt   = htmlspecialchars((string) $footer, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $titleText; ?></title>
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
        .header-brand { vertical-align: bottom; }
        .header-brand img { width: 130px; height: auto; }
        .header-brand .site-name { font-size: 16px; font-weight: bold; color: #222; }
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
            <td class="header-brand">
                <?php if ($logoUrl !== '') : ?>
                    <img src="<?php echo $logoUrl; ?>" alt="<?php echo $siteNameTxt; ?>">
                <?php elseif ($siteNameTxt !== '') : ?>
                    <span class="site-name"><?php echo $siteNameTxt; ?></span>
                <?php endif; ?>
            </td>
            <td class="header-contact">
                <?php if ($siteUrlTxt !== '') : ?><?php echo $siteUrlTxt; ?><?php endif; ?>
            </td>
        </tr>
    </table>

    <h1 class="doc-title"><?php echo $titleText; ?></h1>
    <div class="doc-meta">Generated <?php echo $generated; ?></div>

    <div class="doc-content">
        <?php echo $content; // resolved field content from the action layer ?>
    </div>

    <?php if ($footerTxt !== '') : ?>
    <div class="footer"><?php echo $footerTxt; ?></div>
    <?php endif; ?>
</body>
</html>
