<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Service;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /** Read one of the extracted JSON payloads. */
    protected function data(string $name): array
    {
        $path = database_path("seeders/data/{$name}.json");

        return is_file($path) ? json_decode(file_get_contents($path), true) : [];
    }

    public function run(): void
    {
        $this->users();
        $this->settings();
        $this->pages();
        $this->services();
        $this->projects();
        $this->team();
        $this->faqs();
        $this->testimonials();
        $this->plans();
        $this->blog();

        $this->command->info('Seeded: '
            . Service::count() . ' services, '
            . Project::count() . ' projects, '
            . TeamMember::count() . ' team, '
            . Faq::count() . ' FAQs, '
            . Testimonial::count() . ' testimonials, '
            . Plan::count() . ' plans, '
            . Post::count() . ' posts, '
            . Setting::count() . ' settings.');
    }

    protected function users(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@archilance.net'],
            [
                'name' => 'Archilance Admin',
                'password' => Hash::make('f17@AYDS'),
                'role' => 'owner',
                'email_verified_at' => now(),
            ]
        );
    }

    protected function settings(): void
    {
        foreach ($this->data('settings') as $row) {
            Setting::updateOrCreate(['key' => $row['key']], $row);
        }
    }

    protected function pages(): void
    {
        foreach ($this->data('pages') as $row) {
            Page::updateOrCreate(['slug' => $row['slug']], $row);
        }
    }

    protected function services(): void
    {
        foreach ($this->data('services') as $row) {
            Service::updateOrCreate(['slug' => $row['slug']], $row);
        }
    }

    protected function projects(): void
    {
        foreach ($this->data('projects') as $row) {
            Project::updateOrCreate(['slug' => $row['slug']], $row);
        }
    }

    /**
     * Two passes: every member first, then the parent links. The chart is a
     * self-referencing tree, so a child can appear before its manager.
     */
    protected function team(): void
    {
        $rows = $this->data('team');

        foreach ($rows as $row) {
            $insert = $row;
            unset($insert['parent']);
            TeamMember::updateOrCreate(['slug' => $insert['slug']], $insert);
        }

        $ids = TeamMember::pluck('id', 'slug');
        foreach ($rows as $row) {
            if (! empty($row['parent'])) {
                TeamMember::where('slug', $row['slug'])
                    ->update(['parent_id' => $ids[$row['parent']] ?? null]);
            }
        }
    }

    protected function faqs(): void
    {
        foreach ($this->data('faqs') as $row) {
            $cat = FaqCategory::updateOrCreate(
                ['slug' => $row['category_slug']],
                ['name' => $row['category_name'], 'sort' => $row['category_sort']]
            );

            Faq::updateOrCreate(
                ['question' => $row['question']],
                [
                    'faq_category_id' => $cat->id,
                    'answer' => $row['answer'],
                    'sort' => $row['sort'],
                    'is_published' => true,
                ]
            );
        }
    }

    protected function testimonials(): void
    {
        foreach ($this->data('testimonials') as $row) {
            Testimonial::updateOrCreate(
                ['name' => $row['name'], 'quote' => $row['quote']],
                $row
            );
        }
    }

    protected function plans(): void
    {
        foreach ($this->data('plans') as $row) {
            Plan::updateOrCreate(['name' => $row['name']], $row);
        }
    }

    /** A starter category set plus three posts so the blog is not an empty shell. */
    protected function blog(): void
    {
        $cats = [
            ['slug' => 'revit-bim', 'name' => 'Revit & BIM', 'sort' => 1],
            ['slug' => 'visualisation', 'name' => 'Visualisation', 'sort' => 2],
            ['slug' => 'practice', 'name' => 'Practice', 'sort' => 3],
        ];
        foreach ($cats as $c) {
            PostCategory::updateOrCreate(['slug' => $c['slug']], $c);
        }

        $author = User::where('email', 'admin@archilance.net')->first();

        $posts = $this->data('posts');

        foreach ($posts as $p) {
            $cat = PostCategory::where('slug', $p['category'])->first();

            Post::updateOrCreate(['slug' => $p['slug']], [
                'title' => $p['title'],
                'excerpt' => $p['excerpt'],
                'body' => $p['body'],
                'cover_image' => $p['cover'],
                'image_alt' => $p['title'],
                'post_category_id' => $cat?->id,
                'user_id' => $author?->id,
                'read_minutes' => max(2, (int) round(str_word_count(strip_tags($p['body'])) / 200)),
                'meta_title' => $p['title'] . ' | Archilance LLC',
                'meta_description' => Str::limit($p['excerpt'], 155),
                'published_at' => $p['published_at'],
                'is_published' => true,
            ]);
        }
    }
}
