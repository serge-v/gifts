<?php

require_once '../lib/google-api-php-client/src/Google/autoload.php';

if ($_SERVER['SERVER_ADDR'] == '68.171.217.98')
	include "config_prod.php";
else
	include "config_dev.php";

$app_name = "gifts.voilokov.com";
$client_id = '1068101048338-ojkeas1t2hubi8supo00kgvvutb7rrfa.apps.googleusercontent.com';

session_start();

$client = new Google_Client();
$client->setApplicationName($app_name);
$client->setClientId($client_id);
$client->setClientSecret($oauth_client_secret);
$client->addScope("https://www.googleapis.com/auth/userinfo.profile");
$client->addScope("https://www.googleapis.com/auth/userinfo.email");
$client->setRedirectUri($home_uri.'/loggedin.php');

$service = new Google_Service_Oauth2($client);

date_default_timezone_set('America/New_York');

?>
