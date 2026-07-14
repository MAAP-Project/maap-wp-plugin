<?php
 
/*
 
Plugin Name: MAAP
 
Plugin URI: https://github.com/MAAP-Project/maap-wp-plugin
 
Description: Plugin to add MAAP member API integration and login hook
 
Version: 1.0
 
Author: Brian Satorius & Anil Natha
 
 */

################################################################################
// Define Session Duration (WP session duration default is 48 hrs/2 days)

define( 'MAX_SESSION_DURATION', 24*60*60); // value should be in seconds

################################################################################
/**
 * Create a session so that when authn/authz occurs, it can be used to store CAS information
 */

if (!session_id()) {
    session_start();
}

################################################################################

function my_expiration_filter($seconds, $user_id, $remember){
    return constant("MAX_SESSION_DURATION");
}

function maap_login( $user_login, $user ) {

    // Uncomment the following line to inspect cookie and session information
    // upon login
    //maap_debug_wp_session();

    $cookie_exp = time()+constant("MAX_SESSION_DURATION");

    // ========================================
    // Set PGT Cookie

    $maap_pgt_cookie = 'wp_maap_pgt';

    if(isset($_COOKIE[$maap_pgt_cookie])) {
        unset($_COOKIE[$maap_pgt_cookie]);
    }

    setcookie(
        $maap_pgt_cookie,
        $_SESSION['phpCAS']['pgt'],
        $cookie_exp
    );

    // ========================================
    // Set ClientName Cookie

    $maap_client_name_cookie = 'wp_maap_client_name';

    if(isset($_COOKIE[$maap_client_name_cookie])) {
        unset($_COOKIE[$maap_client_name_cookie]);
    }

    $client_name = array_key_exists('iss', $_SESSION['phpCAS']['attributes']) ? 'GLUU' : 'URS';

    setcookie(
        $maap_client_name_cookie,
        $client_name,
        $cookie_exp
    );

}

function maap_debug_wp_session() {
    echo '$_COOKIE: <pre>';
    echo var_dump($_COOKIE);
    echo "</pre>";

    echo '$_SESSION: <pre>';
    echo var_dump($_SESSION);
    echo "</pre>";

    echo '$_SESSION COOKIE INFO:';
    echo "<pre>";
    echo 'session_name(): ' . session_name() . "\n";
    echo 'session_id(): ' . session_id() . "\n";
    echo "</pre>";

    $proxyTicketDec = $_SESSION['phpCAS']['pgt'];

    echo 'proxyTicketDec: ' . $proxyTicketDec . "\n";

    exit();
    die();
    flush();
}

function maap_admin_enqueue_scripts() {
    wp_enqueue_style('jquery-datatables-css','//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css');
    wp_enqueue_script('jquery-datatables-js','//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js',array('jquery'));
}

function users_endpoint(){
    $response = maap_admin_ajax_endpoint('members');
    wp_send_json($response);
}

function orgs_endpoint(){
    $response = maap_admin_ajax_endpoint('organizations');
    wp_send_json($response);
}

function queues_endpoint(){
    $response = maap_admin_ajax_endpoint('admin/job-queues');
    wp_send_json($response);
}

function s3access_endpoint(){
    $response = maap_admin_ajax_endpoint('admin/s3-access');
    wp_send_json($response);
}

function pre_approved_endpoint(){
    $response = maap_admin_ajax_endpoint('admin/pre-approved');
    wp_send_json($response);
}

function maap_admin_ajax_endpoint($endpoint){
        $maap_pgt_cookie = 'wp_maap_pgt';
        $response = [];
        $maap_api = 'api.' . str_replace("www.", "", $_SERVER['HTTP_HOST']);
        $maap_api_endpoint = 'https://'. $maap_api . '/api/' . $endpoint;
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $maap_api_endpoint);
        curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    
        $headers = array(
            'proxy-ticket:' . $_COOKIE[$maap_pgt_cookie]
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        $result = curl_exec($ch);
    
        $json = json_decode($result);
    
        curl_close($ch);
    
        $response['data'] = !empty($json) ? $json : [];
        $response['recordsTotal'] = !empty($json) ? count($json) : 0;

        return $response;
}

add_action('plugins_loaded', 'maap_plugin_load');
function maap_plugin_load()
{
    // Hook into login chain to initialize cookie upon successful login
    add_filter('auth_cookie_expiration', 'my_expiration_filter', 99, 3);
    add_action('wp_login', 'maap_login', 10, 2);

    // Hooks to display Administrative pages in Wordpress Dashboard
    add_action('admin_menu', 'maap_admin_menu_pages');
    add_action('wp_enqueue_scripts', 'maap_admin_enqueue_scripts', 10 );
    add_action('wp_ajax_users_endpoint', 'users_endpoint'); 
    add_action('wp_ajax_orgs_endpoint', 'orgs_endpoint'); 
    add_action('wp_ajax_queues_endpoint', 'queues_endpoint'); 
    add_action('wp_ajax_s3access_endpoint', 's3access_endpoint');
    add_action('wp_ajax_preapproved_endpoint', 'pre_approved_endpoint');
    add_filter('template_include', 'profile_page_template', 99 );
    add_filter('template_include', 'signup_page_template', 99 );

    // Header "Get Started" CTA -> /signup, plus its pill styling.
    add_filter('wp_nav_menu_items', 'maap_add_get_started_button', 10, 2);
    add_action('wp_enqueue_scripts', 'maap_get_started_button_styles');
}

function maap_admin_menu_pages()
{
    add_menu_page('MAAP Admin', 'MAAP Admin', 'manage_options', 'maap-admin', 'maap_admin_users_callback', 'dashicons-admin-site', 20);
    add_submenu_page('maap-admin', 'MAAP Users', 'Users', 'manage_options', 'maap-admin', 'maap_admin_users_callback');
    add_submenu_page('maap-admin', 'MAAP Orgs', 'Organizations', 'manage_options', 'maap-orgs', 'maap_admin_orgs_callback');
    add_submenu_page('maap-admin', 'MAAP Queues', 'Job Queues', 'manage_options', 'maap-queues', 'maap_admin_queues_callback');
    add_submenu_page('maap-admin', 'MAAP S3 Access', 'S3 Access', 'manage_options', 'maap-s3-access', 'maap_admin_s3access_callback');
    add_submenu_page('maap-admin', 'MAAP Pre-Approved Emails', 'Pre-Approved Emails', 'manage_options', 'maap-pre-approved', 'maap_admin_preappoved_callback');
}

function maap_admin_users_callback()
{
    include __DIR__.'/views/users.php';
}

function maap_admin_orgs_callback()
{
    include __DIR__.'/views/orgs.php';
}

function maap_admin_queues_callback()
{
    include __DIR__.'/views/queues.php';
}

function maap_admin_preappoved_callback()
{
    include __DIR__.'/views/pre-approved.php';
}

function maap_admin_s3access_callback()
{
    include __DIR__.'/views/s3_access.php';
}

function profile_page_template( $template ) {

    if ( strpos($_SERVER['REQUEST_URI'], '/profile/') !== false) {
        $new_template = __DIR__.'/views/public/profile.php';
        if ( '' != $new_template ) {
            return $new_template ;
        }
    }
    return $template;
}

/**
 * Serve the universal sign-up / request-access page at /signup (and /signup/),
 * preserving any query string (e.g. /signup?status=pending sent by the hub and
 * console redirects). Matches the exact path so it won't catch /signups, etc.
 */
function signup_page_template( $template ) {

    $path = strtok( $_SERVER['REQUEST_URI'], '?' ); // drop the query string
    if ( rtrim( $path, '/' ) === '/signup' ) {
        return __DIR__.'/views/public/signup.php';
    }
    return $template;
}

/**
 * Add a prominent "Get Started" button (linking to /signup) to the site header
 * menu, positioned immediately AFTER the Login item. Targets the theme's
 * "primary" nav location (Genesis / Monochrome Pro). home_url() keeps it
 * env-correct (uat -> uat.maap-project.org).
 *
 * We insert after Login (rather than append to the end) because the menu has an
 * empty account/Profile dropdown that otherwise sits between Login and the
 * button and opens a gap. The login item carries "menu-item-object-login" and
 * has no submenu, so the first </li> after it closes it.
 *
 * Alternative: add a Custom Link (URL /signup, label "Get Started") in
 * Appearance > Menus right after Login with the CSS class "maap-get-started";
 * the styling in maap_get_started_button_styles() applies either way.
 */
function maap_add_get_started_button( $items, $args ) {
    if ( ! isset( $args->theme_location ) || $args->theme_location !== 'primary' ) {
        return $items;
    }
    // Logged-out visitors only — signed-in users don't need a "Get Started" CTA.
    if ( is_user_logged_in() ) {
        return $items;
    }
    $url    = esc_url( home_url( '/signup' ) );
    $button = '<li class="menu-item maap-get-started"><a href="' . $url . '">Get Started</a></li>';

    // Insert right after the Login <li>...</li>; fall back to appending.
    if ( preg_match( '/<li[^>]*menu-item-object-login.*?<\/li>/s', $items, $m ) ) {
        return str_replace( $m[0], $m[0] . $button, $items );
    }
    return $items . $button;
}

/**
 * Pill-button styling for the header "Get Started" CTA. Works whether the item
 * is injected by maap_add_get_started_button() or added as a menu item carrying
 * the "maap-get-started" CSS class.
 */
function maap_get_started_button_styles() {
    $css = '.maap-get-started > a, .genesis-nav-menu .maap-get-started > a {'
         . 'display:inline-block;vertical-align:middle;margin-left:20px !important; margin-right:-20px !important;'
         . 'padding:8px 22px !important;background:#0098db !important;color:#fff !important;'
         . 'border-radius:999px !important;font-weight:600;line-height:1.2;'
         . 'text-decoration:none;transition:background .2s ease;}'
         . '.maap-get-started > a:hover, .genesis-nav-menu .maap-get-started > a:hover {'
         . 'background:#00549f !important;color:#fff !important;}'
         // The button now sits right after Login. Strip separators/padding on
         // BOTH sides so it hugs Login with no stray bar before the account
         // dropdown that follows. Genesis spaces items via anchor padding, hence
         // the Login-anchor padding-right trim.
         . '.maap-get-started{margin-left:0 !important;padding-left:0 !important;border-left:0 !important;border-right:0 !important;}'
         . '.maap-get-started::before,.maap-get-started::after,.genesis-nav-menu .maap-get-started::before,.genesis-nav-menu .maap-get-started::after{content:none !important;display:none !important;}'
         . '.maap-get-started + .menu-item::before,.genesis-nav-menu .maap-get-started + .menu-item::before{content:none !important;display:none !important;}'
         . '.menu-item:has(+ .maap-get-started),.genesis-nav-menu .menu-item:has(+ .maap-get-started){margin-right:0 !important;padding-right:0 !important;border-right:0 !important;}'
         . '.menu-item:has(+ .maap-get-started) > a,.genesis-nav-menu .menu-item:has(+ .maap-get-started) > a{padding-right:4px !important;}'
         . '.menu-item:has(+ .maap-get-started)::after,.genesis-nav-menu .menu-item:has(+ .maap-get-started)::after{content:none !important;display:none !important;}';
    wp_register_style( 'maap-get-started', false );
    wp_enqueue_style( 'maap-get-started' );
    wp_add_inline_style( 'maap-get-started', $css );
}

?>
