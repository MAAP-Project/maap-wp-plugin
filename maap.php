<?php
 
/*
 
Plugin Name: MAAP
 
Plugin URI: https://github.com/MAAP-Project/maap-wp-plugin
 
Description: Plugin to add MAAP member API integration and login hook
 
Version: 1.0
 
Author: Brian Satorius & Anil Natha
 
 */

function maap_login( $user_login, $user ) {
    //debug_wp_session();
    
    $maap_pgt_cookie = 'wp_maap_pgt';

    if(isset($_COOKIE[$maap_pgt_cookie])) {
        unset($_COOKIE[$maap_pgt_cookie]);
    }
    
    setcookie(
        $maap_pgt_cookie,
        $_SESSION['phpCAS']['pgt'],
        time()+constant("MAX_SESSION_DURATION")
    );
}

function debug_wp_session() {
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

function pre_approved_endpoint(){
    $response = maap_admin_ajax_endpoint('members/pre-approved');
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
    add_action('wp_login', 'maap_login', 10, 2);

    // Hooks to display Administrative pages in Wordpress Dashboard
    add_action('admin_menu', 'maap_adminpages_add');
    add_action('wp_enqueue_scripts', 'maap_admin_enqueue_scripts', 10 );
    add_action('wp_ajax_users_endpoint', 'users_endpoint'); 
    add_action('wp_ajax_preapproved_endpoint', 'pre_approved_endpoint'); 
    add_filter('template_include', 'profile_page_template', 99 );
}

function maap_adminpages_add()
{
    add_menu_page('MAAP Admin', 'Users', 'manage_options', 'maap-admin', 'maap_admin_callback', 'dashicons-admin-site', 20);
    add_submenu_page('maap-admin', 'MAAP Pre-Approved Emails', 'Pre-Approved Emails', 'manage_options', 'maap-pre-approved', 'maap_admin_preappoved_callback');
}

function maap_admin_callback()
{
    include __DIR__.'/views/users.php';
}

function maap_admin_preappoved_callback()
{
    include __DIR__.'/views/pre-approved.php';
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

?>