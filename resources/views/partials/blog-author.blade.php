{{--
  Author credit box shown at the end of a blog article.
    $author  the TeamMember credited for the post (may be null)
--}}
@if($author)
  <div class="author-box" data-reveal>
    <div class="author-box__id">
      <span class="author-box__pic">
        @if($author->portrait())
          <img src="{{ asset($author->portrait()) }}" width="88" height="88"
               alt="{{ $author->photoAlt() }}" loading="lazy" decoding="async">
        @else
          <span class="author-box__ph" aria-hidden="true">{{ $author->initials() }}</span>
        @endif
      </span>

      <div class="author-box__meta">
        <span class="author-box__eyebrow">Article written by</span>
        <span class="author-box__name-row">
          <b class="author-box__name">{{ $author->name }}</b>
          <span class="author-box__badge">{{ $author->role }}</span>
        </span>
      </div>
    </div>

    <div class="author-box__actions">
      <a class="btn-a btn-a--sm" href="{{ route('blog.index') }}" data-magnetic>
        View all articles
      </a>
      <a class="btn-a btn-a--sm btn-a--ghost" href="{{ route('contact') }}" data-magnetic>
        Start your free trial
      </a>
    </div>
  </div>
@endif
