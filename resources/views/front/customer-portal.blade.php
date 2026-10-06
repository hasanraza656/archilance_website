@extends('layouts.front')

@section('content')

  <section class="page-hero page-hero--flat">
    <div class="page-hero__veil" aria-hidden="true"></div>
    <div class="shell shell--narrow">
      <nav aria-label="Breadcrumb">
        <ol class="crumbs">
          <li><a href="{{ route('home') }}">Home</a></li>
          <li><span aria-current="page">{{ $page?->title }}</span></li>
        </ol>
      </nav>
      @if($page?->eyebrow)<p class="eyebrow" data-reveal>{{ $page->eyebrow }}</p>@endif
      <h1 class="page-hero__title" data-reveal>
        {{ $page?->h1_lead }} @if($page?->h1_gold)<em>{{ $page->h1_gold }}</em>@endif
      </h1>
      @if($page?->lede)<p class="page-hero__lede" data-reveal>{{ $page->lede }}</p>@endif
    </div>
  </section>

  <section class="section section--after-hero section--paper grid-veil" aria-labelledby="portalActionsHeading">
    <div class="shell shell--narrow">
      <h2 class="visually-hidden" id="portalActionsHeading">Subscription actions</h2>

      <div class="steps" data-reveal>
        <button class="step portal-action" type="button" data-portal-open data-portal-tag="View Subscription" data-portal-title="View your subscription">
          <span class="step__no">01</span>
          <h3>View Subscription</h3>
          <p>See your current plan, billing cycle and next payment date.</p>
        </button>
        <button class="step portal-action" type="button" data-portal-open data-portal-tag="Pause Subscription" data-portal-title="Pause your subscription">
          <span class="step__no">02</span>
          <h3>Pause Subscription</h3>
          <p>Temporarily suspend your plan and pick it back up whenever you're ready.</p>
        </button>
        <button class="step portal-action" type="button" data-portal-open data-portal-tag="Cancel Subscription" data-portal-title="Cancel your subscription">
          <span class="step__no">03</span>
          <h3>Cancel Subscription</h3>
          <p>End your subscription. You'll keep access until the end of the current billing period.</p>
        </button>
      </div>

      <p class="portal-note" data-reveal>
        All subscription changes are handled securely by <a href="https://stripe.com" target="_blank" rel="noopener">Stripe</a>, our billing partner — Archilance never sees or stores your payment details.
      </p>
    </div>
  </section>

  @include('partials.portal-dialog')

@endsection

@push('scripts')
<script>
(function () {
  var dlg = document.getElementById('portalDialog');
  if (!dlg) return;

  var panel = dlg.querySelector('.person__panel');
  var closeBtn = document.getElementById('portalDialogClose');
  var tag = document.getElementById('portalDialogTag');
  var title = document.getElementById('portalDialogTitle');
  var lastFocus = null;

  function open(btn) {
    lastFocus = document.activeElement;
    tag.textContent = btn.dataset.portalTag || 'View Subscription';
    title.textContent = btn.dataset.portalTitle || 'Manage your subscription';

    dlg.hidden = false;
    document.body.classList.add('is-locked');
    requestAnimationFrame(function () {
      dlg.classList.add('is-open');
      closeBtn.focus();
    });
  }

  function close() {
    dlg.classList.remove('is-open');
    document.body.classList.remove('is-locked');
    setTimeout(function () { dlg.hidden = true; }, 380);
    if (lastFocus) lastFocus.focus();
  }

  document.querySelectorAll('[data-portal-open]').forEach(function (btn) {
    btn.addEventListener('click', function () { open(btn); });
  });

  closeBtn.addEventListener('click', close);
  dlg.addEventListener('click', function (e) { if (e.target === dlg) close(); });
  document.addEventListener('keydown', function (e) {
    if (dlg.hidden) return;
    if (e.key === 'Escape') { close(); return; }
    if (e.key !== 'Tab') return;

    var focusable = Array.prototype.slice.call(
      panel.querySelectorAll('button, a[href]')
    ).filter(function (x) { return x.offsetParent !== null; });
    if (!focusable.length) return;

    var first = focusable[0], last = focusable[focusable.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  });
})();
</script>
@endpush
