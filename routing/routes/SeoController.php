<?php

namespace routing\routes;

use classes\Methods;

/**
 * SEO Controller
 * Handles sitemap index, child sitemaps, and robots.txt generation
 */
class SeoController {

    /**
     * Generate XML Sitemap Index
     * GET /sitemap.xml
     * Links to child sitemaps (static + per-organisation)
     */
    public static function sitemap(array $args): array {
        $sitemaps = [];

        // Static pages sitemap
        $sitemaps[] = ['loc' => HOST . 'sitemap-static.xml'];

        // Demo sitemap
        $sitemaps[] = ['loc' => HOST . 'sitemap-demo.xml'];

        // Get organisation UIDs with published locations
        $orgUids = Methods::locations()->getOrgUidsWithPublishedLocations();
        foreach ($orgUids as $orgUid) {
            $sitemaps[] = ['loc' => HOST . 'sitemap-org-' . $orgUid . '.xml'];
        }

        $xml = self::buildSitemapIndexXml($sitemaps);
        return ["return_as" => "xml", "result" => $xml];
    }

    /**
     * Generate Static Pages Sitemap
     * GET /sitemap-static.xml
     */
    public static function sitemapStatic(array $args): array {
        $urls = [
            ['loc' => HOST, 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => HOST . 'policies/consumer/privacy', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => HOST . 'policies/consumer/terms', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => HOST . 'policies/merchant/privacy', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => HOST . 'policies/merchant/terms', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => HOST . 'policies/cookies', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => HOST . 'help/faqs/consumer', 'priority' => '0.6', 'changefreq' => 'weekly'],
            ['loc' => HOST . 'help/faqs/merchant', 'priority' => '0.6', 'changefreq' => 'weekly'],
            ['loc' => HOST . 'help/guides/merchant/onboarding', 'priority' => '0.6', 'changefreq' => 'weekly'],
        ];

        $xml = self::buildUrlsetXml($urls);
        return ["return_as" => "xml", "result" => $xml];
    }

    /**
     * Generate Demo Pages Sitemap
     * GET /sitemap-demo.xml
     */
    public static function sitemapDemo(array $args): array {
        $urls = [
            ['loc' => HOST . 'demo', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => HOST . 'demo/cashier', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => HOST . 'demo/consumer', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ];

        $xml = self::buildUrlsetXml($urls);
        return ["return_as" => "xml", "result" => $xml];
    }

    /**
     * Generate Organisation Locations Sitemap
     * GET /sitemap-org-{uid}.xml
     */
    public static function sitemapOrganisation(array $args): array {
        $orgUid = $args['uid'] ?? null;

        if (empty($orgUid)) {
            return ["return_as" => 404];
        }

        // Verify organisation exists
        if (!Methods::organisations()->exists(['uid' => $orgUid])) {
            return ["return_as" => 404];
        }

        // Get published locations for this organisation
        $locations = Methods::locations()->getOrgLocationsForSitemap($orgUid);

        if ($locations->empty()) {
            return ["return_as" => 404];
        }

        $urls = [];
        foreach ($locations->list() as $location) {
            $urls[] = [
                'loc' => HOST . 'merchant/' . $location->slug,
                'priority' => '0.8',
                'changefreq' => 'weekly'
            ];
        }

        $xml = self::buildUrlsetXml($urls);
        return ["return_as" => "xml", "result" => $xml];
    }

    /**
     * Serve favicon
     * GET /favicon.ico
     */
    public static function favicon(array $args): void {
        $faviconPath = __asset(FAVICON);
        header('Content-Type: image/x-icon');
        header('Cache-Control: public, max-age=604800'); // 1 week
        readfile($faviconPath);
        exit;
    }

    /**
     * Generate robots.txt
     * GET /robots.txt
     */
    public static function robots(array $args): array {
        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /api/\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /panel/\n";
        $content .= "Disallow: /dashboard/\n";
        $content .= "Disallow: /checkout/\n";
        $content .= "Disallow: /cron/\n";
        $content .= "Disallow: /migration/\n";
        $content .= "Disallow: /testing/\n";
        $content .= "\n";
        $content .= "Sitemap: " . HOST . "sitemap.xml\n";

        return ["return_as" => "text", "result" => $content];
    }

    /**
     * Build sitemap index XML
     */
    private static function buildSitemapIndexXml(array $sitemaps): string {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemaps as $sitemap) {
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>" . htmlspecialchars($sitemap['loc']) . "</loc>\n";
            if (isset($sitemap['lastmod'])) {
                $xml .= "    <lastmod>" . $sitemap['lastmod'] . "</lastmod>\n";
            }
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    /**
     * Build urlset XML
     */
    private static function buildUrlsetXml(array $urls): string {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
            if (isset($url['lastmod'])) {
                $xml .= "    <lastmod>" . $url['lastmod'] . "</lastmod>\n";
            }
            if (isset($url['changefreq'])) {
                $xml .= "    <changefreq>" . $url['changefreq'] . "</changefreq>\n";
            }
            if (isset($url['priority'])) {
                $xml .= "    <priority>" . $url['priority'] . "</priority>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }
}
