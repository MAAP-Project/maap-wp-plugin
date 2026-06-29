<?php
/**
 * MAAP — Universal Sign-Up / Request Access page.
 *
 * Rendered for /signup via the template_include filter in maap.php.
 *
 * Rendered STANDALONE on purpose (no get_header()/wp_head()):
 *   - its own navbar matches maap-project.github.io/maap-website, so inheriting
 *     the active theme header would produce two stacked headers;
 *   - skipping wp_head() avoids .navbar/.btn CSS collisions with the theme +
 *     Bootstrap and keeps the page pixel-faithful to the approved mockup.
 * If you later need site analytics/SEO plugins to run on this page, add
 * wp_head()/wp_footer() and namespace the CSS below (prefix .navbar/.btn/.hero).
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

// CTA targets. Post-CAS these become the Keycloak entry points.
// hub/oauth_login kicks off EarthData -> Keycloak account creation; a brand-new
// (role-less) account lands back here at /signup?status=pending (see Phase 3).
$maap_get_started_url = "https://{$maap_hub_host}/hub/oauth_login";
$maap_signin_url      = "https://{$maap_hub_host}/hub/oauth_login";

// This route has no backing WP page, so the main query resolved to a 404.
// Force a 200 before any markup is emitted.
if ( ! headers_sent() ) {
    status_header( 200 );
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Request access — MAAP</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?display=swap&family=Ropa+Sans:300,400">
  <style>
    /* ── Tokens lifted verbatim from maap-website/src/css/custom.css ── */
    :root {
      --maap-space-900: #0b1120;
      --maap-space-700: #152347;
      --maap-primary:   #00549f;   /* --ifm-color-primary */
      --maap-blue-500:  #0098db;   /* interactive */
      --maap-blue-400:  #2aa8e0;
      --maap-blue-300:  #66c2e9;
      --maap-gray-800:  #262626;
      --maap-gray-700:  #404040;
      --maap-gray-500:  #737373;
      --maap-gray-200:  #e5e5e5;
      --display: 'Ropa Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      --radius: 12px;
    }
    * { box-sizing: border-box; }
    html { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    body {
      margin: 0;
      font-family: var(--display);
      font-size: 17.5px;
      line-height: 1.7;
      color: var(--maap-gray-800);
      background: var(--maap-space-900);
    }
    a { color: var(--maap-primary); }

    /* ───────── NAVBAR (matches the live site) ───────── */
    .navbar {
      background: #fff;
      border-bottom: 1px solid var(--maap-gray-200);
      box-shadow: 0 1px 12px rgba(0,0,0,.06);
      position: sticky; top: 0; z-index: 100;
    }
    .navbar__inner {
      max-width: 1200px; margin: 0 auto;
      display: flex; align-items: center; gap: 1.5rem;
      padding: .5rem 1.5rem;
    }
    .navbar__logo { height: 40px; width: auto; display: block; }
    .navbar__items { display: flex; gap: 1.5rem; }
    .navbar__link {
      color: var(--maap-gray-700); text-decoration: none;
      font-size: .95rem; font-weight: 400; transition: color .2s;
    }
    .navbar__link:hover { color: var(--maap-primary); }
    .navbar-login-button {
      margin-left: auto;
      background: var(--maap-blue-500); color: #fff;
      border-radius: 6px; padding: 6px 22px;
      font-size: .95rem; font-weight: 400; text-decoration: none;
      border-bottom: 3px solid var(--maap-primary);
      transition: all .25s ease;
    }
    .navbar-login-button:hover { background: var(--maap-primary); transform: translateY(-1px); }

    /* ───────── HERO ───────── */
    .hero {
      position: relative;
      min-height: calc(100vh - 57px);
      display: flex; align-items: center; justify-content: center;
      color: #fff; overflow: hidden;
      background: var(--maap-space-900);
    }
    .hero__bg {
      position: absolute; inset: 0; z-index: 0;
      background: url('<?php echo esc_url( $asset_base . '/hero-prod.jpg' ); ?>') center/cover no-repeat;
    }
    .hero__bg::after {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(180deg, rgba(11,17,32,.55) 0%, rgba(11,17,32,.82) 100%);
    }
    .hero__content {
      position: relative; z-index: 1;
      width: 100%; max-width: 1060px; padding: 3.5rem 2rem;
    }

    .split {
      display: grid; grid-template-columns: 1.05fr .95fr;
      gap: 3.25rem; align-items: center;
    }

    /* Left column */
    .eyebrow {
      display: inline-block; font-size: .78rem; font-weight: 600;
      letter-spacing: .12em; text-transform: uppercase;
      color: var(--maap-blue-300);
      padding: .35rem 1rem; margin-bottom: 1.25rem;
      border: 1px solid rgba(59,130,246,.3);
      border-radius: 100px; background: rgba(59,130,246,.08);
    }
    .lead-title {
      font-family: var(--display); font-weight: 900;
      font-size: clamp(2.2rem, 4.4vw, 3.1rem); line-height: 1.05;
      letter-spacing: .02em; margin: 0 0 1rem;
      background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,.8) 100%);
      -webkit-background-clip: text; background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .lead-sub {
      font-size: 1.18rem; line-height: 1.7;
      color: rgba(255,255,255,.72);
      max-width: 46ch; margin: 0 0 2rem;
    }
    .cta-row { display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
    .btn {
      display: inline-flex; align-items: center; gap: .5rem;
      padding: .9rem 2rem; border-radius: 10px;
      font-family: var(--display); font-weight: 700; font-size: 1rem;
      letter-spacing: .01em; text-decoration: none; cursor: pointer;
      border: none; transition: all .3s ease;
    }
    .btn--primary {
      background: linear-gradient(135deg, var(--maap-blue-500), var(--maap-blue-400));
      color: #fff; box-shadow: 0 4px 20px rgba(0,152,219,.35);
    }
    .btn--primary:hover { box-shadow: 0 8px 32px rgba(0,152,219,.5); transform: translateY(-2px); color: #fff; }
    .signin-line { margin: 1.15rem 0 0; font-size: .95rem; color: rgba(255,255,255,.65); }
    .signin-line a { color: #fff; font-weight: 700; text-decoration: underline; text-underline-offset: 3px; }

    /* Right column — frosted glass timeline */
    .glass {
      background: rgba(255,255,255,.07);
      border: 1px solid rgba(255,255,255,.16);
      border-radius: 16px; padding: 2rem 1.85rem;
      -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px);
      box-shadow: 0 24px 60px rgba(0,0,0,.4);
    }
    .glass__h {
      font-size: .8rem; font-weight: 600; letter-spacing: .12em;
      text-transform: uppercase; color: var(--maap-blue-300);
      margin: 0 0 1.6rem;
    }
    .timeline { list-style: none; margin: 0; padding: 0; position: relative; }
    .t-item { position: relative; padding: 0 0 1.5rem 3.25rem; }
    .t-item:last-child { padding-bottom: 0; }
    .t-item::before {
      content: ''; position: absolute; left: 17px; top: 30px; bottom: -6px;
      width: 2px; background: rgba(255,255,255,.18);
    }
    .t-item:last-child::before { display: none; }
    .t-num {
      position: absolute; left: 0; top: 0;
      width: 36px; height: 36px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-family: var(--display); font-weight: 700; font-size: 1rem;
      background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.25);
      color: rgba(255,255,255,.75);
    }
    .t-item.is-current .t-num {
      background: linear-gradient(135deg, var(--maap-blue-500), var(--maap-blue-400));
      border-color: transparent; color: #fff;
      box-shadow: 0 0 0 5px rgba(0,152,219,.22);
    }
    .t-item.is-done .t-num {
      background: var(--maap-blue-500); border-color: transparent; color: #fff;
    }
    .t-item h3 { font-size: 1.04rem; font-weight: 700; color: #fff; margin: .25rem 0 .25rem; }
    .t-item p { font-size: .92rem; line-height: 1.55; color: rgba(255,255,255,.7); margin: 0; }
    .t-when { display: inline-block; margin-top: .35rem; font-size: .8rem; color: var(--maap-blue-300); font-style: italic; }
    .mailnote {
      display: flex; gap: .6rem; align-items: center;
      margin-top: 1.6rem; padding-top: 1.25rem;
      border-top: 1px solid rgba(255,255,255,.14);
      font-size: .9rem; color: rgba(255,255,255,.82);
    }
    .mailnote svg { flex: none; }

    /* ───────── slim footer ───────── */
    .footer {
      background: var(--maap-space-900); color: rgba(255,255,255,.55);
      text-align: center; font-size: .85rem; padding: 1.5rem;
      border-top: 1px solid rgba(255,255,255,.08);
    }
    .footer a { color: rgba(255,255,255,.78); text-decoration: none; }
    .footer .sep { margin: 0 .6rem; opacity: .4; }

    /* ───────── state toggle ───────── */
    .state-pending { display: none; }
    body[data-state="pending"] .state-new { display: none; }
    body[data-state="pending"] .state-pending { display: block; }
    /* Once registration is initiated this sub-copy carries the key message,
       so it reads at full strength (vs .72 opacity in the new-user state). */
    body[data-state="pending"] .lead-sub { color: #fff; }

    @media (max-width: 820px) {
      .split { grid-template-columns: 1fr; gap: 2.25rem; }
      .navbar__items { display: none; }
      .hero__content { padding: 2.5rem 1.5rem; }
    }
  </style>
</head>
<body>
  <!-- NAVBAR -->
  <header class="navbar">
    <div class="navbar__inner">
      <a class="navbar__brand" href="https://maap-project.org/">
        <img class="navbar__logo" src="<?php echo esc_url( $asset_base . '/maap-logo-prod.png' ); ?>" alt="NASA MAAP">
      </a>
      <nav class="navbar__items">
        <a class="navbar__link" href="https://docs.maap-project.org/">Docs</a>
        <a class="navbar__link" href="https://maap-project.org/news/">News</a>
        <a class="navbar__link" href="https://maap-project.org/community/">Community</a>
      </nav>
      <a class="navbar-login-button" href="<?php echo esc_url( $maap_signin_url ); ?>">Login</a>
    </div>
  </header>

  <!-- HERO -->
  <section class="hero">
    <div class="hero__bg"></div>
    <div class="hero__content">
      <div class="split">

        <!-- LEFT -->
        <div>
          <div class="state state-new">
            <span class="eyebrow">New to MAAP</span>
            <h1 class="lead-title">Request access to MAAP</h1>
            <p class="lead-sub">The collaborative NASA–ESA cloud platform where scientists focus on science, not infrastructure. One EarthData sign-in to get started.</p>
            <div class="cta-row">
              <a class="btn btn--primary" href="<?php echo esc_url( $maap_get_started_url ); ?>">Get started with NASA EarthData &rarr;</a>
            </div>
            <p class="signin-line">Already approved? <a href="<?php echo esc_url( $maap_signin_url ); ?>">Sign in</a></p>
          </div>

          <div class="state state-pending">
            <span class="eyebrow">Registration received</span>
            <h1 class="lead-title">You're in the queue</h1>
            <p class="lead-sub">Thanks for signing in with NASA EarthData. Your MAAP account is being reviewed — there's nothing more you need to do right now. We'll email you the moment it's approved.</p>
            <p class="signin-line">Questions? <a href="mailto:support@maap-project.org">support@maap-project.org</a></p>
          </div>
        </div>

        <!-- RIGHT -->
        <aside class="glass">
          <p class="glass__h">What to expect</p>
          <ol class="timeline">
            <li class="t-item s1 is-current">
              <span class="t-num">1</span>
              <h3>Sign in with NASA EarthData</h3>
              <p>Use your existing EarthData (EDL) login, or create one in minutes. You'll accept the one-time MAAP EULA right here during sign-in.</p>
              <span class="t-when">~5 min if you're new to EarthData</span>
            </li>
            <li class="t-item s2">
              <span class="t-num">2</span>
              <h3>Automatic account review</h3>
              <p>Your request is submitted automatically and reviewed by the MAAP team.</p>
              <span class="t-when">1–3 business days</span>
            </li>
            <li class="t-item s3">
              <span class="t-num">3</span>
              <h3>You're approved</h3>
              <p>We email you the moment your account is activated — then sign in and launch your workspace.</p>
              <span class="t-when">Automatic email</span>
            </li>
          </ol>
          <div class="mailnote">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 5h16v14H4z" stroke="#66c2e9" stroke-width="2"/><path d="M4 6l8 6 8-6" stroke="#66c2e9" stroke-width="2"/></svg>
            <span>We'll email you when your access is ready.</span>
          </div>
        </aside>

      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="footer">
    <a href="https://docs.maap-project.org/">Documentation</a>
    <span class="sep">&bull;</span>
    <a href="mailto:support@maap-project.org">Support</a>
    <span class="sep">&bull;</span>
    <a href="https://maap-project.org/">maap-project.org</a>
    <div style="margin-top:.5rem; opacity:.7;">© <?php echo esc_html( date('Y') ); ?> MAAP Project · Funded by NASA and ESA · Operated by 2i2c</div>
  </footer>

  <script>
    // Production: hub/console redirect appends ?status=pending for post-auth users.
    if (new URLSearchParams(location.search).get('status') === 'pending') {
      document.body.setAttribute('data-state', 'pending');
      var s1 = document.querySelector('.t-item.s1');
      var s2 = document.querySelector('.t-item.s2');
      if (s1) { s1.classList.remove('is-current'); s1.classList.add('is-done'); s1.querySelector('.t-num').innerHTML = '&#10003;'; }
      if (s2) { s2.classList.add('is-current'); }
    }
  </script>
</body>
</html>
