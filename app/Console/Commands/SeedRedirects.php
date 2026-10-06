<?php

namespace App\Console\Commands;

use App\Models\Redirect;
use Illuminate\Console\Command;

/**
 * Inserts the exact-match redirects from the SEO spreadsheet's Redirects
 * sheet, plus the items confirmed in planning (internship/feedback-form,
 * the 19 review pages, the 18 orphaned project case studies, the now-renamed
 * own routes, and the aliza-malick 410). Pattern rows that can't live as an
 * exact `from` string are handled separately in ApplyRedirects, not here.
 *
 * Re-runnable: every row is an updateOrCreate keyed on `from`, so running
 * this again after a correction just updates the existing row.
 */
class SeedRedirects extends Command
{
    protected $signature = 'redirects:seed';

    protected $description = 'Insert the exact-match redirects from the SEO migration spreadsheet';

    public function handle(): int
    {
        $rows = $this->rows();

        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $existing = Redirect::where('from', $row['from'])->first();
            $action = $existing ? 'update' : 'create';

            $redirect = Redirect::updateOrCreate(
                ['from' => $row['from']],
                [
                    'to' => $row['to'] ?? null,
                    'status' => $row['status'],
                    'is_active' => true,
                ]
            );

            if ($action === 'create') {
                $created++;
                $this->line("  <fg=green>+ create</> {$row['from']} → " . ($row['to'] ?? '(410)') . " [{$row['status']}]");
            } elseif ($redirect->wasChanged()) {
                $updated++;
                $this->line("  <fg=yellow>~ update</> {$row['from']} → " . ($row['to'] ?? '(410)') . " [{$row['status']}]");
            }
        }

        $this->newLine();
        $this->info("Done. {$created} created, {$updated} updated, " . count($rows) . ' total rows processed.');

        return self::SUCCESS;
    }

    /** @return array<int, array{from: string, to: ?string, status: int}> */
    protected function rows(): array
    {
        return [
            // ---- Own-site renamed routes (urgent: these were silently served
            // by the generic catch-all with the wrong template before this
            // existed — see verification notes during Part B implementation).
            ['from' => '/contact/', 'to' => '/contact-us/', 'status' => 301],
            ['from' => '/pricing/', 'to' => '/pricing-plans/', 'status' => 301],
            ['from' => '/faqs/', 'to' => '/faq/', 'status' => 301],
            ['from' => '/blog/', 'to' => '/blogs/', 'status' => 301],

            // ---- Confirmed this session, not stale sheet defaults.
            // Sheet row 27/28 originally said /career/ -> /about-us/ and
            // /internship-program/ -> /about-us/ as a stopgap "until the page
            // is rebuilt" — but /career/ is now a real page, so it is not a
            // redirect source at all, and internship goes to /career/, not
            // /about-us/, per the decision made before building these pages.
            ['from' => '/internship-program/', 'to' => '/career/', 'status' => 302],
            ['from' => '/feedback-form/', 'to' => '/contact-us/', 'status' => 301],
            // Sheet row 29 (/customer-portal/) only applies "if the page
            // isn't kept" — it was kept and rebuilt, so no redirect needed.

            // ---- Admin redirect manager / exact rows from the sheet.
            ['from' => '/archx/', 'to' => 'https://archx.archi/meet', 'status' => 301],
            ['from' => '/home/', 'to' => '/', 'status' => 301],
            ['from' => '/team/alishba-bilal/', 'to' => '/team/alishba-bilal-2/', 'status' => 301],
            ['from' => '/team/hamza-malik/', 'to' => '/team/malik-hamza-khalid/', 'status' => 301],
            ['from' => '/team/m-asad-2/', 'to' => '/team/m-asad/', 'status' => 301],
            ['from' => '/author/tehreem/', 'to' => '/team/tehreem-puri/', 'status' => 301],
            ['from' => '/author/tehreem/amp/', 'to' => '/team/tehreem-puri/', 'status' => 301],
            ['from' => '/category/blog/', 'to' => '/blogs/', 'status' => 301],
            ['from' => '/architectural-design-bim-3d-visualization-services-in-the-usa/', 'to' => '/architecture-bim-3d-visualization-services-usa/', 'status' => 301],
            ['from' => '/service/3d-animation-services/', 'to' => '/service/3d-architectural-animation-services/', 'status' => 301],

            // ---- Old Rank Math sitemap files.
            ['from' => '/sitemap_index.xml', 'to' => '/sitemap.xml', 'status' => 301],
            ['from' => '/post-sitemap.xml', 'to' => '/sitemap.xml', 'status' => 301],
            ['from' => '/page-sitemap.xml', 'to' => '/sitemap.xml', 'status' => 301],
            ['from' => '/project-sitemap.xml', 'to' => '/sitemap.xml', 'status' => 301],
            ['from' => '/service-sitemap.xml', 'to' => '/sitemap.xml', 'status' => 301],
            ['from' => '/category-sitemap.xml', 'to' => '/sitemap.xml', 'status' => 301],
            ['from' => '/project-type-sitemap.xml', 'to' => '/sitemap.xml', 'status' => 301],
            ['from' => '/softwares-used-sitemap.xml', 'to' => '/sitemap.xml', 'status' => 301],

            // ---- Gone (410): Rank Math Local SEO file, no replacement.
            ['from' => '/locations.kml', 'to' => null, 'status' => 410],

            // ---- Orphaned project case studies (no current equivalent) —
            // fallback to the index, per the sheet's own default, confirmed.
            ['from' => '/project/office-design/', 'to' => '/projects/', 'status' => 301],
            ['from' => '/project/dock-road-retail-center/', 'to' => '/projects/', 'status' => 301],
            ['from' => '/project/quiet-retreat/', 'to' => '/projects/', 'status' => 301],
            ['from' => '/project/master-bathroom/', 'to' => '/projects/', 'status' => 301],
            ['from' => '/project/commercial-project/', 'to' => '/projects/', 'status' => 301],
            ['from' => '/project/modern-cottage/', 'to' => '/projects/', 'status' => 301],
            ['from' => '/project/modern-residence/', 'to' => '/projects/', 'status' => 301],
            ['from' => '/project/bathroom-design/', 'to' => '/projects/', 'status' => 301],
            // These two have a suggested closest match in the sheet itself —
            // using it rather than the generic index fallback.
            ['from' => '/project/living-room/', 'to' => '/project/modern-living-room/', 'status' => 301],
            ['from' => '/project/contemporary-facade-design-with-hyper-realistic-renders/', 'to' => '/project/high-rise-facade/', 'status' => 301],
            ['from' => '/project/facade-design/', 'to' => '/project/high-rise-facade/', 'status' => 301],
            ['from' => '/project/hallie-development-company/', 'to' => '/projects/', 'status' => 301],
            ['from' => '/project/chicken-coop/', 'to' => '/projects/', 'status' => 301],
            ['from' => '/project/kids-room/', 'to' => '/projects/', 'status' => 301],
            ['from' => '/project/bath-animation/', 'to' => '/projects/', 'status' => 301],
            ['from' => '/project/additional-building/', 'to' => '/projects/', 'status' => 301],
            ['from' => '/project/revit-families/', 'to' => '/projects/', 'status' => 301],
            ['from' => '/project/bedroom-interior-design/', 'to' => '/projects/', 'status' => 301],

            // ---- Old WooCommerce/membership pages — all gone, no replacement.
            ['from' => '/shop/', 'to' => null, 'status' => 410],
            ['from' => '/cart/', 'to' => null, 'status' => 410],
            ['from' => '/checkout/', 'to' => null, 'status' => 410],
            ['from' => '/checkout-subscription/', 'to' => null, 'status' => 410],
            ['from' => '/checkout-page/', 'to' => null, 'status' => 410],
            ['from' => '/my-account/', 'to' => null, 'status' => 410],
            ['from' => '/my-account-2/', 'to' => null, 'status' => 410],
            ['from' => '/member-login/', 'to' => null, 'status' => 410],
            ['from' => '/member-logout/', 'to' => null, 'status' => 410],
            ['from' => '/register/', 'to' => null, 'status' => 410],
            ['from' => '/lost-password/', 'to' => null, 'status' => 410],
            ['from' => '/subscription-plan/', 'to' => null, 'status' => 410],
            ['from' => '/thank-you-page/', 'to' => null, 'status' => 410],
            ['from' => '/member-tos-page/', 'to' => null, 'status' => 410],
            ['from' => '/public-individual-page/', 'to' => null, 'status' => 410],

            // ---- Deleted team profiles — already 404 on WordPress too.
            ['from' => '/team/isbah-qasim/', 'to' => null, 'status' => 410],
            ['from' => '/team/kiran-tahira/', 'to' => null, 'status' => 410],
            ['from' => '/team/hala-sundas/', 'to' => null, 'status' => 410],
            ['from' => '/team/momna-zafar/', 'to' => null, 'status' => 410],
            ['from' => '/team/aliza-malick/', 'to' => null, 'status' => 410],

            // 19 old /review/*/ pages are NOT listed individually here — the
            // sheet itself defines this as a pattern rule (^/review/.* -> /),
            // which also catches any review URL not enumerated in the sheet.
            // See ApplyRedirects for the pattern-rule implementation.
        ];
    }
}
