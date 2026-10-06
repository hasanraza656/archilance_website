{{-- Subscription management modal for /customer-portal. Reuses the same
     structural + visual pattern as partials/person-dialog.blade.php's
     #person dialog (same .person classes, same open/close/focus-trap
     mechanics in main.js) rather than inventing a new modal system. --}}
<div class="person" id="portalDialog" role="dialog" aria-modal="true" aria-labelledby="portalDialogTitle" hidden>
  <div class="person__panel person__panel--portal">
    <button class="person__close" type="button" id="portalDialogClose" aria-label="Close">
      <svg class="ico" aria-hidden="true"><use href="#i-close"></use></svg>
    </button>

    <p class="person__tag" id="portalDialogTag">View Subscription</p>
    <h3 class="person__name" id="portalDialogTitle">Manage your subscription</h3>
    <p class="person__blurb">
      You'll be redirected to our billing partner, Stripe, where you can sign in with your account email and manage your subscription.
    </p>

    <ol class="portal-steps">
      <li>
        <span class="portal-steps__n">1</span>
        <div>
          <b>Enter your email</b>
          <p>Use the email address your subscription is registered under.</p>
        </div>
      </li>
      <li>
        <span class="portal-steps__n">2</span>
        <div>
          <b>Check your inbox</b>
          <p>Stripe sends a secure sign-in link to that address — no password needed.</p>
        </div>
      </li>
      <li>
        <span class="portal-steps__n">3</span>
        <div>
          <b>Manage your subscription</b>
          <p>View your plan, pause it, or cancel it directly from the Stripe portal.</p>
        </div>
      </li>
    </ol>

    <a class="btn-a person__cta" id="portalDialogCta" href="https://billing.stripe.com/p/login/9AQ9BfdWx2Kw2l2cMM" target="_blank" rel="noopener">
      Proceed to your subscription
      <svg class="ico ico--arrow" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
    </a>
  </div>
</div>
