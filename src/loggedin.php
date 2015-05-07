<?php

require_once 'init.php';

if ($_GET['error']) {
	echo $_GET['error'];
	echo '<br><a href="index.php">Try again</a>';
}
else if ($_GET['code']) {
	$client->authenticate($_GET['code']);
	$_SESSION['access_token'] = $client->getAccessToken();
	header('Location: '.$home_uri);
}

?>
