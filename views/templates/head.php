<?php
/**
 * @var array|null $styleList
 * @var string|null $pageHeaderTitle
 * @var array $pageMeta
 */

// Build full title
$fullTitle = !empty($pageHeaderTitle) ? htmlspecialchars($pageHeaderTitle) . ' | ' . BRAND_NAME : BRAND_NAME;

// Get current URL for canonical
$currentUrl = HOST . ltrim($_SERVER['REQUEST_URI'] ?? '', '/');
$currentUrl = strtok($currentUrl, '?'); // Remove query string

// Default meta values
$defaultDescription = BRAND_NAME . ' gør det nemt at dele dine betalinger op eller betale senere. Fleksible betalingsløsninger for både forbrugere og forhandlere.';
$defaultOgImage = __asset(OG_IMAGE);

// Extract meta values (controller override > path constant > defaults)
$metaDescription = $pageMeta['description'] ?? $defaultDescription;
$metaCanonical = $pageMeta['canonical'] ?? $currentUrl;
$metaRobots = $pageMeta['robots'] ?? 'index,follow';
$ogTitle = $pageMeta['og_title'] ?? $pageHeaderTitle ?? BRAND_NAME;
$ogDescription = $pageMeta['og_description'] ?? $metaDescription;
$ogImage = $pageMeta['og_image'] ?? $defaultOgImage;
$ogUrl = $pageMeta['og_url'] ?? $metaCanonical;
$twitterCard = $pageMeta['twitter_card'] ?? 'summary_large_image';

// Structured data (JSON-LD) - only output when explicitly provided
$schemaData = $pageMeta['schema'] ?? null;
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<link rel="icon" href="<?=__asset(FAVICON)?>" type="image/x-icon" />

<!-- SEO Meta Tags -->
<title><?=$fullTitle?></title>
<meta name="description" content="<?=htmlspecialchars($metaDescription)?>">
<link rel="canonical" href="<?=htmlspecialchars($metaCanonical)?>" />
<meta name="robots" content="<?=htmlspecialchars($metaRobots)?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?=htmlspecialchars($ogTitle)?>">
<meta property="og:description" content="<?=htmlspecialchars($ogDescription)?>">
<meta property="og:image" content="<?=htmlspecialchars($ogImage)?>">
<meta property="og:url" content="<?=htmlspecialchars($ogUrl)?>">
<meta property="og:site_name" content="<?=BRAND_NAME?>">

<!-- Twitter -->
<meta name="twitter:card" content="<?=htmlspecialchars($twitterCard)?>">
<meta name="twitter:title" content="<?=htmlspecialchars($ogTitle)?>">
<meta name="twitter:description" content="<?=htmlspecialchars($ogDescription)?>">
<meta name="twitter:image" content="<?=htmlspecialchars($ogImage)?>">

<?php
// Only output structured data when explicitly provided via $pageMeta['schema']
if (!empty($schemaData)):
?>
<script type="application/ld+json">
<?=json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)?>
</script>
<?php endif; ?>

<?php
if(!is_null($styleList)) {
    foreach ($styleList as $asset) {
        echo assets($asset, "css");
    }
}
?>