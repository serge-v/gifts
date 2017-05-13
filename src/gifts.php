<?php

require_once 'init.php';

$debug = 1;

# include('sendmail.php');
include('gifts_db.php');
include('actions.php');

$userid = '';
$action = '';
$error = '';
$msg = '';

if (isset($_COOKIE['uid'])) {
	$userid = $_COOKIE['uid'];
}
if (isset($_GET['action'])) {
	$action = $_GET['action'];
}
if (isset($_GET['e'])) {
	$error = $_GET['e'];
}
if (isset($_GET['m'])) {
	$msg = $_GET['m'];
}

if (!$error) {
	if (isset($_SESSION['access_token']) && $_SESSION['access_token'])
	{
		$client->setAccessToken($_SESSION['access_token']);
	}

	if ($client->getAccessToken())
	{
		try {
			$userData = $service->userinfo->get();
		}
		catch (Google_Auth_Exception $e) {
		}
	
		if ($userData) {
			$_SESSION['access_token'] = $client->getAccessToken();
			$userid = findUserIdByEmail($userData->email);
		
			if ($userid == 0) {
				list($err, $password) = createUser($userData->email, 'name', 'family_name');
				if ($err != '')
				{
					respond($err);
				}
				$userid = findUserIdByEmail($userData->email);
	#			sendMail($username, 'your gifts.voilokov.com account', 'Password is '.$password);
	#			respond('', 'Check email for login information');
			}
		}
	
	}
}

if ($action == "log")
{
    if ($userid != '')
    {
        $userInfo = getUserInfo($userid);
	
        if ($userInfo['userEmail'] == 'voilokov@gmail.com')
        {
            echo system("pwd; tac log.txt | awk -f logfilter.awk");
            exit;
        }
    }
    echo 'invalid user';
    exit;
}

log_info('userid:'.$userid.', action: '.$action);
foreach ($_POST as $k=>$v)
{
    log_debug('post: '.$k.'='.$v);
}

$friendid = '';
if (isset($_GET['fid'])) {
	$friendid = $_GET['fid'];
}

if ($friendid > 0)
{
    log_info('userid:'.$friendid);
    $viewerid = $_COOKIE["vid"];
    if ($viewerid == '')
    {
        $viewerid = createFriend($friendid);
        setcookie("vid", $viewerid, time()+30*24*3600);
    }
    log_info('viewerid:'.$viewerid);
    
    if ($action == '')
    {
        $action = "view_gifts";
    }
}
else
{
    $find_info = '';
    if (isset($_GET['q'])) {
        $find_info = $_GET['q'];
    }
    if ($find_info != '')
    {
        $action = 'find_user';
    }
}

function respond($error = '', $msg = '')
{
    if ($error != '')
    {
        $s = '?e='.$error;
    }
    if ($msg != '')
    {
        $s = '?m='.$msg;
    }

    header("Location: /".$s);
    exit;
}

switch ($action)
{
case "logout":
	if (isset($_SESSION['access_token']) && $_SESSION['access_token'])
	{
		unset($_SESSION['access_token']);
		$client->revokeToken();
		header('Location: ' . filter_var($home_uri, FILTER_SANITIZE_URL));
	}
	else
	{
		setcookie("uid", '', time());
		respond();
	}

case "login":
    if ($_POST['login'] == 'Login')
    {
        $username = $_POST['useremail'];
        $password = $_POST['password'];
        log_debug('username:'.$username.', password: '.$password);

        if ($username == '')
        {
            respond('Invalid login');
        }
        elseif ($password == '')
        {
            respond('Invalid password');
        }
        else
        {
            $userid = userLogin($username, $password);
            if ($userid > 0)
            {
                setcookie("uid", $userid, time()+3600);
				log_debug('cookie:'.$userid);
                respond();
            }
            else
            {
                respond('Invalid email or password');
            }
        }
    }
    break;

case "signup":
    $username = $_POST['useremail'];
    $userfname = $_POST['userfname'];
    $userlname = $_POST['userlname'];

    if ($username == '')
    {
        respond('Email is empty');
    }
    if ($userfname == '')
    {
        respond('Firstname is empty');
    }
    if ($userlname == '')
    {
        respond('Lastname is empty');
    }

    list($error, $password) = createUser($username, $userfname, $userlname);
    if ($error != '')
    {
        respond($error);
    }
    sendMail($username, 'gifts.voilokov.com credentials', 'Password is '.$password);
    respond('', 'Check email for login information');

case "change_password":
    $currpassword = $_POST['currpassword'];
    $newpassword = $_POST['newpassword'];
    $newpasswordc = $_POST['newpasswordc'];

    if ($currpassword == '')
    {
        $error = 'Current password is empty';
    }
    elseif ($newpassword == '')
    {
        $error = 'New password is empty';
    }
    elseif ($newpassword != $newpasswordc)
    {
        $error = 'New password doesn\'t match confirmed password';
    }
    else
    {
        $error = updatePassword($userid, $currpassword, $newpassword);
    }
    $redir = "Location: /?action=settings";
    if ($error != '')
    {
        $redir .= "&e=".$error;
    }
    else
    {
        $redir .= "&e=ok"; 
    }
    header($redir);
    exit;

case "save_settings":
    $userfname = $_POST['userfname'];
    $userlname = $_POST['userlname'];
    $userphoto = $_FILES['userphoto']['tmp_name'];
    updateSettings($userid, $userfname, $userlname, $userphoto);
    header("Location: /?action=settings");
    exit;

case "submit_friends":
    $friends = $_POST['addfriend_box'];
    addFriends($userid, $friends);
    header("Location: /?action=settings#".$_POST['scroll']);
    exit;

case "submit_holidays":
    $friends = $_POST['addholiday_box'];
    addHolidays($userid, $friends);
    header("Location: /?action=settings#".$_POST['scroll']);
    exit;

case "submit_email_options":
    $state = $_POST['send_email_check'];
    updateEmailCheckState($userid, $state);
    header("Location: /?action=settings#".$_POST['scroll']);
    exit;

case "notify_holiday":
    $holidayid = $_GET['holidayid'];
    list($to, $subject, $text) = createHolidayEmail($userid, $holidayid);
    sendMail($to, $subject, $text);
    respond();

case "submit_gift":
    $cancel = $_POST['cancel'];
    if ($cancel == 'Cancel')
    {
        respond();
    }
    $arr = preg_split("/\n/", $_POST['addgift_box'], 2);
    $gift_name = $arr[0];
    if (count($arr) > 1)
    {
        $gift_descr = $arr[1];
    }
    $gift_url = $_POST['gift_url'];
    $gift_picture = $_POST['gift_picture'];
    addGift($userid, $gift_name, $gift_url, $gift_picture, $gift_descr);
    respond();

case "delete_gift":
    $giftid = $_GET['giftid'];
    deleteGift($userid, $giftid);
    respond();

case "view_gifts":
    $friendid = $_GET['fid'];
    break;

case "select_gift":
    $giftid = $_GET['giftid'];
    selectGift($giftid, $viewerid);
    header("Location: /?fid=".$friendid);
    exit;
    break;

case "unselect_gift":
    $friendid = $_GET['fid'];
    $giftid = $_GET['giftid'];
    unselectGift($giftid);
    header("Location: /?fid=".$friendid);
    exit;
    break;

case "paste_gift":
    $gift_url = $_POST['gift_url'];
    list($gift_name, $gift_picture) = parseGiftInfo($gift_url);
#    if (!preg_match("!^https?://!", $gift_name) && $gift_picture == '')
#    {
#        addGift($userid, $gift_name, '', '', '');
#        respond();
#    }
        
    $action = "add_gift";
    break;

case "update_descr":
    $giftid = $_POST['giftid'];
    $gift_descr = $_POST['gift_descr'];
    updateGift($userid, $giftid, $gift_descr);
    respond();
    break;

case "find_user":
    $found_users = findUser($find_info);
    if (count($found_users) == 1)
    {
        header("Location: /?fid=".$found_users[0]['userID']);
        exit;
    }
    break;
case "send_emails":
	$m = sendHolidayEmails();
	echo $m;
	exit;
}

if ($userid != '')
{
    $userInfo = getUserInfo($userid);
}

?>
<html>
<head>
    <link href="main.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="script.js"></script>
<script>
function init()
{
	if (window.location.hash != null) {
		var s = parseInt(window.location.hash.substring(1));
		window.scrollTo(0, s);
	}
}
</script>
</head>
<body onload="init()">

<a class="top" href="?">&nbsp;WHAT I WANT GIFTS</a>
<span class="w1"></span>
<?php if ($userid != '') { ?>

	Welcome, <?php echo $userInfo['firstName'] ?>
	<a class="add" href="?action=logout">[logout]</a>
	<a class="add" href="?action=settings">[settings]</a>
	<?php if ($userInfo['userEmail'] == 'voilokov@gmail.com') { ?>
		<a class="add" href="?action=log">LOG</a>
	<?php } ?>
<?php } ?>
<br>
<?php
if ($error != '' && $error != 'ok') { ?>
    <br><br>
    <hr class="eopen"/>
    &nbsp;&nbsp;<font color="red"><b>ERROR: <?php echo $error ?><b></font>
    <hr class="eclose"/>
    <br>
    <br>
<?php
}
elseif ($msg != '') 
{
    echo '&nbsp;&nbsp;'.$msg.'<br><br>';
}

if ($action == 'view_gifts') 
{
    include "select_gifts_form.php";
}
elseif ($action == 'find_user')
{
    include "select_found_user_form.php";
}
elseif ($action == 'settings')
{
    include 'settings_form.php';
} 
elseif ($userid == '')
{
	include "find_form.php";
	$authUrl = $client->createAuthUrl();
	?>
	<hr class="sep">
	<b>Login using Google account:</b><br><br>
	<a href="<?= $authUrl ?>"><img height="40px" src="gbutton.png"></a>
	<br>
	<?php
	include "login_form.php";
	include "signup_form.php";	
}
elseif ($userid != '')
{
    include "gifts_form.php";
}
?>
</body>
</html>

