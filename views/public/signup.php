<?php
/**
 * MAAP — Universal Sign-Up / Request Access page (TEMPORARY, current-site skin).
 *
 * Rendered for /signup via the template_include filter in maap.php.
 *
 * This interim version matches the CURRENT site (light theme, real WordPress
 * header/footer via get_header()/get_footer()) so there's no visual jump moving
 * between the main site and signup until the v2 redesign launches. The v2
 * (immersive) version is kept at signup-v2-hi-contrast.php to swap in later.
 *
 * Same behavior as v2: env-aware Earthdata Login CTA, ?status=pending state, and the
 * accessibility work (role=list, aria-current, sr-only step states, aria-hidden
 * decorations). CSS is scoped under .maap-signup to avoid theme/Bootstrap
 * collisions; the hero is full-bleed via a 100vw break-out.
 *
 * Template Name: MAAP Sign Up
 *
 * @author BSatorius
 */

$asset_base = plugins_url( 'img', __FILE__ );

// Pair the hub with the web environment from the request host, mirroring how
// maap.php derives the API host: uat.maap-project.org -> staging hub, else prod.
$maap_hub_host = ( strpos( $_SERVER['HTTP_HOST'] ?? '', 'uat.' ) !== false )
    ? 'staging.hub.maap-project.org'
    : 'hub.maap-project.org';
$maap_get_started_url = "https://{$maap_hub_host}/hub/oauth_login";
$maap_signin_url      = "https://{$maap_hub_host}/hub/oauth_login";

// /signup has no backing WP page, so the main query resolved to a 404; force 200
// and clear the 404 state so the <title> isn't rendered as "Page not found".
if ( ! headers_sent() ) {
    status_header( 200 );
}
global $wp_query;
if ( $wp_query ) {
    $wp_query->is_404 = false;
}
add_filter( 'document_title_parts', function ( $parts ) {
    $parts['title'] = 'MAAP Sign Up';
    return $parts;
} );

get_header();
?>
<div class="maap-signup">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?display=swap&family=Ropa+Sans:300,400">
  <style>
    .maap-signup {
      --navy: #16357e; --navy-dark: #0f2757; --ink: #1c2b39; --body: #3a4653;
      --muted: #6b7683; --line: #e2e6ea; --accent: #00549f;
      --sans: 'Ropa Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      --serif: Georgia, 'Times New Roman', Times, serif;
      font-family: var(--serif); color: var(--body); font-size: 17px; line-height: 1.7;
    }
    .maap-signup h1, .maap-signup h2, .maap-signup h3 { font-family: var(--sans); }
    .maap-signup a { color: var(--accent); }

    /* full-bleed break-out so the hero + section span edge-to-edge inside the
       theme's (possibly width-constrained) content wrapper */
    .maap-signup .ms-hero, .maap-signup .ms-expect { width: 100vw; margin-left: calc(50% - 50vw); }

    .maap-signup .ms-sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }

    /* ───────── HERO ───────── */
    .maap-signup .ms-hero { position: relative; overflow: hidden; }
    .maap-signup .ms-hero__bg {
      position:absolute; inset:0; z-index:0;
      background: url('<?php echo esc_url( $asset_base . '/hero-prod.jpg' ); ?>') center/cover no-repeat;
    }
    .maap-signup .ms-hero__inner { position: relative; z-index:1; max-width: 1200px; margin: 0 auto; padding: 72px 20px; min-height: 480px; display:flex; align-items:center; }
    .maap-signup .ms-card {
      background: rgba(255,255,255,.9);
      -webkit-backdrop-filter: blur(2px); backdrop-filter: blur(2px);
      box-shadow: 0 10px 34px rgba(16,28,45,.18);
      border-radius: 2px; padding: 40px 44px 36px; max-width: 560px;
    }
    .maap-signup .ms-eyebrow { font-family: var(--sans); font-size: .95rem; color: var(--muted); letter-spacing:.02em; margin: 0 0 .5rem; }
    .maap-signup .ms-title { color: var(--ink); font-size: clamp(2rem, 4vw, 2.9rem); line-height: 1.08; font-weight: 700; margin: 0 0 1rem; }
    .maap-signup .ms-sub { font-family: var(--serif); color: var(--body); font-size: 1.12rem; line-height: 1.65; margin: 0 0 1.6rem; max-width: 44ch; }
    .maap-signup .ms-btn {
      display:inline-block; font-family: var(--sans); font-weight: 700; font-size: 1.05rem;
      background: var(--navy); color:#fff; text-decoration:none;
      padding: 13px 28px; border-radius: 4px; border: none; cursor:pointer;
      box-shadow: 0 6px 16px rgba(16,53,126,.28); transition: background .2s ease, transform .05s ease;
    }
    .maap-signup .ms-btn:hover { background: var(--navy-dark); color:#fff; }
    .maap-signup .ms-btn:active { transform: translateY(1px); }
    .maap-signup .ms-signin { font-family: var(--sans); margin: 1rem 0 0; font-size: 1.1rem; color: var(--muted); }
    .maap-signup .ms-signin a { color: var(--navy); font-weight: 600; }

    /* ───────── "What to expect" ───────── */
    .maap-signup .ms-expect { background:#fff; padding: 32px 20px 68px; }
    .maap-signup .ms-expect__inner { max-width: 1120px; margin: 0 auto; }
    .maap-signup .ms-expect__inner > h2 { text-align:center; color: var(--ink); font-size: 1.75rem; font-weight: 700; margin: 0 0 1.73rem; }
    .maap-signup .ms-steps { list-style:none; margin:0; padding:0; display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 2.4rem; align-items: start; }
    .maap-signup .ms-step { text-align:center; }
    .maap-signup .ms-step-num {
      width: 52px; height: 52px; border-radius: 50%; margin: 0 auto .9rem;
      display:flex; align-items:center; justify-content:center;
      font-family: var(--sans); font-weight: 700; font-size: 1.15rem;
      background: #eef1f6; color: var(--navy); border: 2px solid #d8dfe8;
    }
    .maap-signup .ms-step.is-current .ms-step-num { background: var(--navy); color:#fff; border-color: var(--navy); box-shadow: 0 0 0 4px rgba(22,53,126,.14); }
    .maap-signup .ms-step.is-done .ms-step-num { background: var(--navy); color:#fff; border-color: var(--navy); }
    .maap-signup .ms-step h3 { font-size: 1.16rem; color: var(--ink); margin: 0 0 .5rem; }
    .maap-signup .ms-step p { font-family: var(--serif); color: var(--body); font-size: 1rem; line-height: 1.6; margin: 0 auto .5rem; max-width: 34ch; }
    .maap-signup .ms-when { font-family: var(--sans); font-style: italic; font-size: .9rem; color: var(--muted); }
    .maap-signup .ms-communities { margin-top: 1rem; }
    .maap-signup .ms-communities .lbl { font-family: var(--sans); font-size: .82rem; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); display:block; margin-bottom: .55rem; }
    .maap-signup .ms-chips { display:flex; flex-wrap:wrap; gap:.5rem; justify-content:center; }
    .maap-signup .ms-chip { font-family: var(--sans); font-size: .92rem; background:#eef1f6; color: var(--navy); border:1px solid #d8dfe8; border-radius:999px; padding:.4rem .9rem; }
    .maap-signup .ms-mailnote { display:flex; align-items:center; justify-content:center; gap:.6rem; margin: 2.8rem auto 0; font-family: var(--sans); color: var(--ink); font-size: 1rem; }
    .maap-signup .ms-mailnote svg { flex:none; }

    /* ───────── state toggle (?status=pending) ───────── */
    .maap-signup .ms-state-pending { display:none; }
    body[data-state="pending"] .maap-signup .ms-state-new { display:none; }
    body[data-state="pending"] .maap-signup .ms-state-pending { display:block; }

    @media (max-width: 820px) {
      .maap-signup .ms-steps { grid-template-columns: 1fr; gap: 2rem; }
      .maap-signup .ms-hero__inner { padding: 44px 20px; min-height: 0; }
      .maap-signup .ms-card { max-width: none; }
    }
  </style>

  <!-- HERO -->
  <section class="ms-hero">
    <div class="ms-hero__bg"></div>
    <div class="ms-hero__inner">
      <div class="ms-card">
        <div class="ms-state ms-state-new">
          <p class="ms-eyebrow">New to MAAP</p>
          <h1 class="ms-title">Request access to MAAP</h1>
          <p class="ms-sub">The collaborative NASA–ESA cloud platform where scientists focus on science, not infrastructure. Open to NASA-Affiliated research and ESA Biomass users.</p>
          <a class="ms-btn" href="<?php echo esc_url( $maap_get_started_url ); ?>">Get started with Earthdata Login</a>
          <p class="ms-signin">Already approved? <a href="<?php echo esc_url( $maap_signin_url ); ?>">Sign in</a></p>
        </div>

        <div class="ms-state ms-state-pending">
          <p class="ms-eyebrow">Registration received</p>
          <h1 class="ms-title">You're in the queue</h1>
          <p class="ms-sub">Thanks for signing in with Earthdata Login. Your MAAP account is being reviewed — there's nothing more you need to do right now. We'll email you the moment it's approved.</p>
          <p class="ms-signin">Questions? <a href="mailto:support@maap-project.org">support@maap-project.org</a></p>
        </div>
      </div>
    </div>
  </section>

  <!-- WHAT TO EXPECT -->
  <section class="ms-expect">
    <div class="ms-expect__inner">
      <h2>What to expect</h2>
      <ol class="ms-steps" role="list">
        <li class="ms-step s1 is-current" aria-current="step">
          <div class="ms-step-num" aria-hidden="true">1</div>
          <h3>Sign in with Earthdata Login</h3>
          <p>Use your existing Earthdata Login account, or create one in minutes. You'll accept the one-time MAAP EULA right here during sign-in.</p>
          <span class="ms-when">~ 3 minutes</span>
          <span class="ms-tstate ms-sr-only">Current step</span>
        </li>
        <li class="ms-step s2">
          <div class="ms-step-num" aria-hidden="true">2</div>
          <h3>Automatic account review</h3>
          <p>Your request is submitted automatically and reviewed by the MAAP team.</p>
          <span class="ms-when">1–3 business days</span>
          <span class="ms-tstate ms-sr-only">Upcoming</span>
        </li>
        <li class="ms-step s3">
          <div class="ms-step-num" aria-hidden="true">3</div>
          <h3>You're approved</h3>
          <p>We email you the moment your account is activated — then sign in and launch your workspace.</p>
          <span class="ms-when">Automatic email</span>
          <span class="ms-tstate ms-sr-only">Upcoming</span>
        </li>
      </ol>
      <p class="ms-mailnote">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="M4 5h16v14H4z" stroke="#16357e" stroke-width="2"/><path d="M4 6l8 6 8-6" stroke="#16357e" stroke-width="2"/></svg>
        We'll email you when your access is ready.
      </p>
    </div>
  </section>
</div>

<script>
  // Production: hub/console redirect appends ?status=pending for post-auth users.
  if (new URLSearchParams(location.search).get('status') === 'pending') {
    document.body.setAttribute('data-state', 'pending');
    var s1 = document.querySelector('.maap-signup .ms-step.s1');
    var s2 = document.querySelector('.maap-signup .ms-step.s2');
    if (s1) {
      s1.classList.remove('is-current'); s1.classList.add('is-done');
      s1.removeAttribute('aria-current');
      s1.querySelector('.ms-step-num').innerHTML = '&#10003;';
      var st1 = s1.querySelector('.ms-tstate'); if (st1) st1.textContent = 'Completed';
    }
    if (s2) {
      s2.classList.add('is-current'); s2.setAttribute('aria-current', 'step');
      var st2 = s2.querySelector('.ms-tstate'); if (st2) st2.textContent = 'Current step';
    }
  }
</script>
<?php
get_footer();
