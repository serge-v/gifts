<?php
require_once('Mail.php');

function sendMail($to, $subject, $body)
{
	$headers = array ('From' => $mail_from,
	'Bcc' => $to,
	'Subject' => $subject);
	$smtp = Mail::factory('smtp',
	array ('host' => $mail_host,
	 'auth' => true,
	 'port' => 465,
	 'username' => $mail_username,
	 'password' => $mail_password,
	 'debug' => false));

    mail($to, $subject, $body);
}

?>
