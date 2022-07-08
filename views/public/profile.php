<?php
/**
 * Monochrome Pro.
 *
 * A MAAP template to allow custom profile management
 *
 * Template Name: MAAP Profile
 *
 * @author  BSatorius
 */
?>

<?php get_header(); ?>

<?php

// Get information from cookies
$maap_pgt_cookie = 'wp_maap_pgt';
$pgt = $_COOKIE[$maap_pgt_cookie];

$wp_maap_client_name = 'wp_maap_client_name';
$client = $_COOKIE[$wp_maap_client_name] ? $_COOKIE[$wp_maap_client_name] : "UNKNOWN";
$client_name = "Unknown";
if( strtoupper($client) == "URS" ) {
    $client_name = "EarthData (URS)";
} elseif ( strtoupper($client) == "GLUU" ) {
    $client_name = "ESA (Gluu)";
}

// Set API variables
$maap_api = 'api.' . str_replace("www.", "", $_SERVER['HTTP_HOST']);
$maap_api_profile = 'https://'. $maap_api . '/api/members/self';
$maap_api_sshKey = $maap_api_profile . '/sshKey';

$self_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$ssh_key_name = '';
$ssh_key_dt = '';


//If submitting a new key, update the MAAP profile
//Otherwise, load the existing profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ch = curl_init();

    $key_file = curl_file_create(realpath($_FILES['file_upl']['tmp_name']), $_FILES['file_upl']['type'], $_FILES['file_upl']['name']);

    $headers = array(
        'proxy-ticket:' . $pgt
    );
    $data = array('file' => $key_file);
    curl_setopt($ch, CURLOPT_URL, $maap_api_sshKey);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $result = curl_exec($ch);

    $json = json_decode($result);

    $ssh_key_name = $json->public_ssh_key_name;
    $ssh_key_dt = $json->public_ssh_key_modified_date;

    curl_close($ch);
    
} elseif (isset($_GET['del'])) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $maap_api_sshKey);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    
    $headers = array(
        'proxy-ticket:' . $pgt,
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);

    $json = json_decode($result);

    $ssh_key_name = $json->public_ssh_key_name;
    $ssh_key_dt = $json->public_ssh_key_modified_date;

    curl_close($ch);
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $maap_api_profile);
curl_setopt($ch, CURLOPT_HTTPGET, TRUE);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

$headers = array(
    'proxy-ticket:' . $pgt,
);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
$json = json_decode($result);

$ssh_key_name = $json->public_ssh_key_name;
$ssh_key_dt = $json->public_ssh_key_modified_date;
$first_name = $json->first_name;
$last_name = $json->last_name;
$status = $json->status;
$email = $json->email;
$username = $json->username;
$organization = $json->organization;

curl_close($ch);

?>
 
<div id="primary" class="content-area">
    <main id="genesis-content" class="content">

        <div class="entry-content" itemprop="text">
            <?php if ($pgt) { ?>
                <form name="file_up" id="file_up" action="" method="POST" enctype="multipart/form-data">
                    
                    <h1>Profile</h1>

                    <table id="profile-details" class="container-border">
                        <?php

                            // Create Status HTML
                            $status_html = '<div class="error-text"><i aria-hidden="true" data-hidden="true" class="fa fa-exclamation-circle icon-size-lg icon-margin-right"></i><strong>Unknown</strong></div><div>If you have questions about your account, send an email to <a href="mailto:support@maap-project.org">support@maap-project.org</a>.</div>';

                            if( strtoupper($status) === "SUSPENDED" ) {
                                $status_html = '<div class="error-text"><i aria-hidden="true" data-hidden="true" class="fa fa-exclamation-circle icon-size-lg icon-margin-right"></i><strong>Pending Activation</strong></div><div>If you have questions about your account’s activation, send an email to <a href="mailto:support@maap-project.org">support@maap-project.org</a>.</div>';
                            } elseif ( strtoupper($status) === "ACTIVE" ) {
                                $status_html = '<div class="success-text"><i aria-hidden="true" data-hidden="true" class="fa fa-check-circle icon-size-lg icon-margin-right"></i> <strong>Active</strong></div><div>If you have questions about your account, send an email to <a href="mailto:support@maap-project.org">support@maap-project.org</a>.</div>';
                            }

                            // Save profile information
                            $isUrs = ( strtoupper($client) == "URS" );
                            $user_fields = array(
                                array("Name", $first_name . " " . $last_name, "", $isUrs),
                                array("Username", $username, "", $isUrs),
                                array("Email", $email, "", $isUrs),
                                array("Account Service", $client_name, "The authentication service your account is linked to.", True),
                                array("MAAP Account Status", $status_html, "", $isUrs)
                            );

                            foreach( $user_fields as $user_field ) {
                                if( $user_field[3] ) {
                                    echo '<tr>';
                                    echo '<td>' . ($user_field[2] != "" ? '<span title="' . $user_field[2] . '">' : "" ) . $user_field[0] . '</span></td>';
                                    echo '<td>' . $user_field[1] . '</td>';
                                    echo '</tr>';
                                }
                            }
                            
                        ?>
                    </table>

                    <?php if( $client != "UNKNOWN") { ?>
                    <div class="wp-block-atomic-blocks-ab-accordion ab-block-accordion">
                        <details>
                            <summary class="ab-accordion-title">Updating your account information</summary>
                            <div class="ab-accordion-text container-border">

                                <?php

                                    $profile_url = "";
                                    if( strtoupper($client) === "URS" ) {

                                        $profile_url = "https://urs.earthdata.nasa.gov"; // UAT and OPS environment
                                        if( $_SERVER['SERVER_NAME'] == 'dit.maap-project.org' ) {
                                            $profile_url = "https://uat.urs.earthdata.nasa.gov"; // DIT environment
                                        }

                                    } elseif ( strtoupper($client) === "GLUU" ) {

                                        // Only one environment for ESA at the moment
                                        $profile_url = "https://iam.val.esa-maap.org";

                                    }

                                    echo 'To update your MAAP profile, simply update your <a href="' . $profile_url . '">' . $client_name . ' Profile</a> and the next time you login to MAAP, your MAAP profile will be synchronized with your ' . $client_name . ' profile.';

                                ?>

                            </div>
                        </details>
                    </div>
                    <?php } ?>
                    
                    <h3>Public SSH Key</h3>
                    <div>Your public SSH key allows you to establish a secure connection between your computer and your MAAP workspaces. To add an SSH key, you need to generate one or use and existing key.</div>
            <?php if ($ssh_key_name != '' ) { ?>
                    <div style="margin-top: 10px; font-size: 16px">
                            <i aria-hidden="true" data-hidden="true" class="fa fa-key settings-list-icon d-none d-sm-block" style="margin-right: 5px;color: #777777;"></i>
                            <?php echo $ssh_key_name . ' - created ' . $ssh_key_dt ?>   
                            <a href="<?php echo $self_link . '?del=1' ?>"><i aria-hidden="true" data-hidden="true" class="fa fa-trash" style="font-size: 14px; margin-left: 10px"></i></a>
                    </div>    
            <?php } ?>
                    <div style="margin-top: 10px;">
                        <input type="file" name="file_upl" id="file_upl">
                    </div>
                </form> 
            <?php } else { echo '<p><a href="/">Log back in</a> to view your profile.</p>';  } ?>
        </div>

</div>
 
    </main><!-- .content -->
 
</div><!-- .content-area -->
 
<script>

    document.getElementById("file_upl").onchange = function() {
        document.getElementById("file_up").submit();
    }

</script>

<?php get_footer(); ?>
