<?php
/**
 * PDF HTML template (rendered by mPDF).
 *
 * The footer is rendered separately as an mPDF page footer (see PdfBuilder),
 * so it is not part of this body markup.
 *
 * Available variables (set in PdfBuilder::buildHtml):
 *
 * @var string $logo        Logo image URL (or local path), may be empty.
 * @var int    $logoWidth   Max logo width in px.
 * @var string $brand       Brand primary colour (hex).
 * @var string $title       Document heading.
 * @var string $content     Pre-rendered inner HTML (field tokens already resolved).
 * @var string $generatedAt Human-readable generation timestamp.
 * @var string $siteName    WordPress site name (header fallback when no logo).
 */

if (!defined('ABSPATH')) {
    exit;
}

$brandColour = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');
$logoUrl     = htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');
$logoW       = (int) $logoWidth;
$titleText   = htmlspecialchars($title !== '' ? $title : 'Form Submission', ENT_QUOTES, 'UTF-8');
$generated   = htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8');
$siteNameTxt = htmlspecialchars((string) $siteName, ENT_QUOTES, 'UTF-8');
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
        }
        .header img { width: <?php echo $logoW; ?>px; height: auto; }
        .header .site-name { font-size: 18px; font-weight: bold; color: #222; }
        h1.doc-title {
            color: <?php echo $brandColour; ?>;
            font-size: 18px;
            margin: 0 0 4px 0;
        }
        .doc-meta { font-size: 11px; color: #777; margin-bottom: 18px; }
        .doc-content { color: #222; }
    </style>
</head>
<body>
    <div class="header">
        <?php if ($logoUrl !== '') : ?>
            <img src="<?php echo $logoUrl; ?>" width="<?php echo $logoW; ?>" alt="<?php echo $siteNameTxt; ?>">
        <?php elseif ($siteNameTxt !== '') : ?>
            <span class="site-name"><?php echo $siteNameTxt; ?></span>
        <?php endif; ?>
    </div>

    <h1 class="doc-title"><?php echo $titleText; ?></h1>
    <div class="doc-meta">Generated <?php echo $generated; ?></div>

    <div class="doc-content">
        <?php echo $content; // resolved field content from the action layer ?>
    </div>
</body>
</html>
