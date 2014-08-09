<?php

function login()
{
	if ($_POST['login'] == 'Login')
	{
		$username = $_POST['username'];
		$password = $_POST['password'];
		$userid = userLogin($username, $password);
		if ($userid > 0)
		{
			setcookie("uid", $userid, time()+3600);
			header("Location: /");
			exit;
		}
		else
		{
			$error = "Invalid email or password";
		}
	}
}

function signup()
{
	if ($_POST['signup'] == 'Sign Up')
	{
		$username = $_POST['username'];
		$error = createUser($username);
		if ($error == '' && $userid > 0)
		{
			$needConfirm = 1;
		}
		else
		{
			$error = "Invalid email";
		}
	}
	else
	{
		$error = "Invalid request";
	}
	
	return array($error, $needConfirm);
}

