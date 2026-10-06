<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Service;
use App\Models\TeamMember;
use Illuminate\Console\Command;

/**
 * Corrects Service, Project and TeamMember slugs to match the old
 * WordPress site exactly, per the SEO migration spreadsheet. Updates both
 * the live DB row and the matching seed JSON entry in the same pass —
 * per this project's established rule (team-seed-is-source-of-truth): a
 * DB-only fix gets silently undone by the next `migrate:fresh --seed`.
 */
class CorrectSlugs extends Command
{
    protected $signature = 'slugs:correct {--dry-run : Show what would change without writing anything}';

    protected $description = 'Correct Service, Project and Team slugs to match the old WordPress URLs';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $this->correct(Service::class, 'database/seeders/data/services.json', $this->serviceSlugs(), $dry);
        $this->newLine();
        $this->correct(Project::class, 'database/seeders/data/projects.json', $this->projectSlugs(), $dry);
        $this->newLine();
        $this->correct(TeamMember::class, 'database/seeders/data/team.json', $this->teamSlugs(), $dry);

        return self::SUCCESS;
    }

    /** @param array<string, string> $map current slug => corrected slug */
    protected function correct(string $model, string $jsonPath, array $map, bool $dry): void
    {
        $this->info(class_basename($model) . ':');

        $fullPath = base_path($jsonPath);
        $data = json_decode(file_get_contents($fullPath), true);
        $jsonChanged = false;

        foreach ($map as $from => $to) {
            $row = $model::where('slug', $from)->first();

            if (! $row) {
                $this->line("  <fg=red>! skip</>   {$from} — no matching DB row (already corrected, or slug mismatch)");
                continue;
            }

            $this->line("  <fg=yellow>~</> {$from} → {$to}" . ($dry ? ' (dry run)' : ''));

            if (! $dry) {
                $row->slug = $to;
                $row->save();
            }

            foreach ($data as &$entry) {
                if (($entry['slug'] ?? null) === $from) {
                    $entry['slug'] = $to;
                    $jsonChanged = true;
                }
            }
            unset($entry);
        }

        if ($jsonChanged && ! $dry) {
            file_put_contents(
                $fullPath,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
            );
            $this->line("  <fg=green>✓</> {$jsonPath} updated");
        } elseif ($jsonChanged && $dry) {
            $this->line("  (dry run — {$jsonPath} not written)");
        }
    }

    /** @return array<string, string> */
    protected function serviceSlugs(): array
    {
        return [
            'revit-drafting' => 'drafting-in-revit-services',
            // construction-permit-sets keeps its slug — only the route's
            // folder prefix changes (/services/ -> /service/), already
            // handled by the route rename, not a slug correction.
            'point-cloud-to-bim' => 'point-cloud-to-bim-services',
            '3d-modeling-rendering' => '3d-modeling-and-rendering-services',
            '3d-architectural-animation' => '3d-architectural-animation-services',
            'architecture-design' => 'architectural-design-services',
            'interior-design' => 'interior-design-services',
            'landscape-architecture' => 'landscape-design-services',
        ];
    }

    /** @return array<string, string> */
    protected function projectSlugs(): array
    {
        return [
            'cozy-interior-fireplace' => 'cozy-interior-with-fireplace',
            'modern-living-room' => 'modern-living-room-interior',
            'modern-residential-interior' => 'modern-residential-interior-design',
            'modern-gym' => 'gym',
            'existing-building-google-maps' => 'existing-building-from-google-maps',
            'racing-track-landscape' => 'racing-track',
            'high-rise-facade' => 'high-rise-building-facade',
        ];
    }

    /** @return array<string, string> */
    protected function teamSlugs(): array
    {
        return [
            'asad-abbasi' => 'm-asad',
            'faheem-ur-rehman' => 'm-faheem-ur-rehman-khan',
            'ifrah-kazmi' => 'ifrah',
            'azka-gul' => 'azka-gul-chaudhary',
            'shahnawaz' => 'shahnawaz-revit-modeler',
            'syed-hussain' => 'syed-hussain-abbas-zaidi',
            'syeda-khadija' => 'syeda-khadija-zahra-naqvi',
            'alishba' => 'alishba-bilal-2',
            'eyob-dagne' => 'eyob-dagne-firew',
            'hamza-malik' => 'malik-hamza-khalid',
            'waleed-azhar' => 'muhammad-waleed-azhar',
            'noor-ul-ain-tariq' => 'noor-ul-ain',
            'mehreen-iftikhar' => 'mehreen',
            'hamza-javaid' => 'muhammad-hamza-javed',
            'rashid-subhani' => 'muhammad-rashid-subhani',
        ];
    }
}
