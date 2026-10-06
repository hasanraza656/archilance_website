<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\Testimonial;

class PageController extends Controller
{
    /** SEO array for a Page row, falling back to the global defaults. */
    protected function seoFor(?Page $page, array $extra = []): array
    {
        return array_merge([
            'title' => $page?->meta_title ?: $page?->title,
            'description' => $page?->meta_description,
            'keywords' => $page?->meta_keywords,
            'image' => $page?->og_image ?: $page?->hero_image,
            'canonical' => $page?->canonical ?: url()->current(),
            'noindex' => (bool) ($page?->noindex),
        ], array_filter($extra, fn ($v) => $v !== null));
    }

    public function home()
    {
        $page = Page::where('slug', 'home')->first();

        return view('front.home', [
            'page' => $page,
            'seo' => $this->seoFor($page),
            'services' => Service::published()->ordered()->get(),
            'projects' => Project::published()->ordered()->get(),
            'plans' => Plan::published()->orderBy('sort')->get(),
            'testimonials' => Testimonial::published()->orderBy('sort')->get(),
            'faqs' => Faq::published()->with('category')->orderBy('sort')->limit(8)->get(),
            'posts' => Post::live()->with('category')->latest('published_at')->limit(3)->get(),
        ]);
    }

    public function pricing()
    {
        $page = Page::where('slug', 'pricing')->first();

        return view('front.pricing', [
            'page' => $page,
            'seo' => $this->seoFor($page),
            'plans' => Plan::published()->orderBy('sort')->get(),
            'services' => Service::published()->ordered()->get(),
            // The pricing questions are the ones that actually block a signup,
            // so pull that category rather than the general list.
            'faqs' => Faq::published()->with('category')
                ->whereHas('category', fn ($q) => $q->where('slug', 'pricing'))
                ->orderBy('sort')->get(),
            'testimonials' => Testimonial::published()->orderBy('sort')->get(),
        ]);
    }

    public function about()
    {
        $page = Page::where('slug', 'about')->first();

        return view('front.about', [
            'page' => $page,
            'seo' => $this->seoFor($page),
            'leadership' => $leadership = TeamMember::published()
                ->where('is_leadership', true)->orderBy('sort')->get(),

            // The band renders leadership as flat cards with no subtree, so a
            // branch is anyone outside it who either sits at the top or reports
            // into the band. Without the second case, reassigning someone to a
            // leadership member would drop them — and their whole subtree —
            // off the chart entirely.
            'divisions' => TeamMember::published()
                ->where('is_leadership', false)
                ->where(fn ($q) => $q->whereNull('parent_id')
                    ->orWhereIn('parent_id', $leadership->pluck('id')))
                ->with('childrenTree')->orderBy('sort')->get(),
            // has_profile, not a photo: the photo filter was a proxy for "is a
            // real person, not a department card", and it silently undercounted
            // once team members without a portrait joined the chart.
            'teamCount' => TeamMember::withProfile()->count(),
            'teamGroups' => TeamMember::TEAMS,
            'groupCounts' => TeamMember::published()
                ->selectRaw('team, COUNT(*) as n')->groupBy('team')->pluck('n', 'team'),
        ]);
    }

    public function faq()
    {
        $page = Page::where('slug', 'faq')->first();
        $categories = FaqCategory::with('publishedFaqs')->orderBy('sort')->get()
            ->filter(fn ($c) => $c->publishedFaqs->isNotEmpty())->values();

        return view('front.faq', [
            'page' => $page,
            'seo' => $this->seoFor($page),
            'categories' => $categories,
            'total' => $categories->sum(fn ($c) => $c->publishedFaqs->count()),
        ]);
    }

    public function contact()
    {
        $page = Page::where('slug', 'contact')->first();

        return view('front.contact', [
            'page' => $page,
            'seo' => $this->seoFor($page),
            'services' => Service::published()->ordered()->get(),
        ]);
    }

    public function customerPortal()
    {
        $page = Page::where('slug', 'customer-portal')->first();

        return view('front.customer-portal', [
            'page' => $page,
            'seo' => $this->seoFor($page),
        ]);
    }

    /**
     * Page slugs that are reserved by a dedicated route above (home, about,
     * services, projects, faq, contact, blog, pricing, customer-portal).
     * Each of those Page rows is real content storage for its own route's
     * controller method, not a one-off page — the slug is an internal
     * identifier, not a URL path, so the catch-all must never resolve one of
     * these by slug even though the row exists and is published. Without
     * this, renaming a route's URI (e.g. /pricing -> /pricing-plans) leaves
     * the OLD path falling through to this catch-all, which would then
     * happily serve the "pricing" Page row's content at the wrong URL
     * instead of 404ing (where ApplyRedirects could actually catch it).
     */
    protected const RESERVED_SLUGS = [
        'home', 'about', 'services', 'projects', 'faq', 'contact', 'blog', 'pricing', 'customer-portal',
    ];

    /**
     * Editable one-off pages (privacy policy, terms, anything added later) —
     * and, per the SEO spreadsheet, blog posts too: "posts and pages share
     * one resolver" now that posts serve at root level instead of /blog/.
     * Post is checked first since Page::slug and Post::slug are confirmed
     * non-colliding (verified against the live data before this was built).
     */
    public function show(string $slug, BlogController $blog)
    {
        abort_if(in_array($slug, self::RESERVED_SLUGS, true), 404);

        $post = Post::where('slug', $slug)->first();
        if ($post) {
            return $blog->show($post);
        }

        $page = Page::where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('front.page', [
            'page' => $page,
            'seo' => $this->seoFor($page),
        ]);
    }
}
