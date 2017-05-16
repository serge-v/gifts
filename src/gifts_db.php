<?php

function log_add($s, $type)
{
	$f = fopen('log.txt', 'a');
	fwrite($f, date('Y-m-d H:i:s').' '.$type.' '.$s."\n");
	fclose($f);
}

function log_info($s)
{
	log_add($s, 'INFO ');
}

function log_debug($s)
{
	log_add($s, 'DEBUG');
}

function log_error($s)
{
	log_add($s, 'ERROR');
}

function log_fail($s)
{
	$trace = debug_backtrace();
	$text .= "\n  ".$trace[0]['file'].'('.$trace[0]['line'].')';
	for ($i = 1; $i < count($trace); $i++)
	{
		$text .= "\n  ".$trace[$i]['file'].'('.$trace[$i]['line'].'): '
		         .$trace[$i]['function']."('".implode("', '", $trace[$i]['args'])."')";
	}
	log_add($s.$text, 'FAIL ');
	setcookie("uid", '', time());
	header("Location: /");
	exit;
}

function connect()
{
	global $dbhost, $dbuser, $dbpass, $dbname;
	$conn = mysqli_connect($dbhost, $dbuser, $dbpass) or log_fail('Error connecting to mysql: '.mysqli_error($conn));
	mysqli_select_db($conn, $dbname) or log_fail('Could not select database: '.mysqli_error($conn));
	return $conn;
}

function createPassword($length)
{
	$chars = "234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$i = 0;
	$password = "";
	while ($i < $length) {
		$password .= $chars{mt_rand(0,strlen($chars))};
		$i++;
	}
	return $password;
}

function createUser($username, $userfname, $userlname)
{
	$conn = connect();

	$password = createPassword(8);
	$query = "select userID from USER where userEmail = '".$username."'";
	$result = mysqli_query($conn, $query) or log_fail ('createUser (select): '. mysqli_error($conn));
	if ($row = mysqli_fetch_array($result))
	{
		if ($userfname != '')
		{
			$add_info = ", firstName = '".mysqli_real_escape_string($conn, $userfname)."'";
		}
		if ($userlname != '')
		{
			$add_info .= ", lastName = '".mysqli_real_escape_string($conn, $userlname)."'";
		}

		$query = "update USER set password = '".$password."' ".$add_info." where userID = ".$row[0];
		mysql_queryi($conn, $query) or log_fail ('createUser (update): query:'.$query.', error:'.mysqli_error($conn));
		log_debug('user already exists: '.$username.' '.$query);
		return array('', $password);
	}
	else
	{
		$query = "insert into USER(userEmail, password, firstName, lastName, registrationCode, registrationDate)
		         values ('".$username."','".$password."', '".mysqli_real_escape_string($conn, $userfname)."',
		         '".mysqli_real_escape_string($conn, $userlname)."', '1', curdate())";

		if ($result = mysql_query($query))
		{
			$query = 'SELECT LAST_INSERT_ID() FROM USER';
			$result = mysqli_query($conn, $query) or log_fail ('createUser (insert): '. mysqli_error($conn));
			$row = mysqli_fetch_array($result);
			$userid = $row[0];
			log_info('createUser: '.$userid);
		}
		else
		{
			$error = 'Cannot create user. Reason: '. mysqli_error($conn);
		}
	}

	return array($error, $password);
}

function updatePassword($userid, $currpassword, $newpassword)
{
	$conn = connect();

	$q = "select * from USER where userID = ".$userid;
	$result = mysqli_query($conn, $q) or log_fail('updatePassword: q:'.$q.', error:'.mysqli_error($conn));
	if ($row = mysqli_fetch_array($result))
	{
		if ($row['password'] != $currpassword)
		{
			return 'Current password is invalid';
		}
		$q = "update USER set password = '".mysqli_real_escape_string($conn, $newpassword)."' where userID = ".$userid;
		mysqli_query($conn, $q) or log_fail ('updatePassword: q:'.$q.', error:'.mysqli_error($conn));
	}
	else
	{
		return 'Cannot find user';
	}
}

function updateSettings($userid, $userfname, $userlname, $userphoto)
{
	$conn = connect();

	$query = "update USER set ".
	         "  firstName = '".mysqli_real_escape_string($conn, $userfname)."'".
	         ", lastName = '".mysqli_real_escape_string($conn, $userlname)."'".
	         " where userID = ".$userid;
	mysqli_query($conn, $query) or
	log_fail ('updateSettings: query:'.$query.', error:'.mysqli_error($conn));

	if ($userphoto != '')
	{
		if (!move_uploaded_file($userphoto, 'photos/'.$userid.'.jpg'))
		{
			log_fail('Cannot save photo');
		}
	}
}

function userLogin($username, $password)
{
	$conn = connect();

	$query = "SELECT userID FROM USER where userEmail = '".$username."' AND password = '".$password."'";
	$result = mysqli_query($conn, $query) or log_fail ('userLogin: '. mysqli_error($conn));
	if ($row = mysqli_fetch_array($result))
	{
		$userid = $row[0];
	}
	else
	{
		$userid = 0;
	}
	return $userid;
}

function getUserInfo($userid)
{
	$conn = connect();

	$query = "select * from USER u where userid = ".$userid;
	$result = mysqli_query($conn, $query) or log_fail('getUserInfo: '.$query.'. Error: '. mysqli_error($conn));
	$row = mysqli_fetch_array($result);

	return $row;
}

function findUser($find_info)
{
	$conn = connect();

	$query = "select userID, firstName, lastName from USER
	         where userEmail like '%".$find_info."%'
	         or firstName like '%".$find_info."%'
	         or lastName like '%".$find_info."%'";
	$result = mysqli_query($conn, $query) or log_fail('findUser: '. mysqli_error($conn));
	$rows = null;
	while ($row = @mysqli_fetch_array($result, MYSQLI_ASSOC))
	{
		$rows[] = $row;
	}
	return $rows;
}

function findUserIdByEmail($email)
{
	$conn = connect();

	$query = "select userID from USER where userEmail = '".$email."'";
	$result = mysqli_query($conn, $query) or log_fail('findUserByEmail: '. mysqli_error($conn));
	if ($row = @mysqli_fetch_array($result, MYSQLI_ASSOC))
	{
		return $row['userID'];
	}
	return 0;
}

function getFriends($userid, $separator)
{
	$conn = connect();

	$query = "select * from USER_FRIEND uf, FRIEND f where uf.friendID = f.friendID and userID = ".$userid;
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn).'\n');

	$content = "";
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
	{
		$content .= $row['email'].$separator."\n";
	}
	return $content;
}

function getGifts($userid)
{
	$conn = connect();

	$query = "select * from USER_GIFT ug, GIFT g
	         where ug.giftID = g.giftID and userID = ".$userid."
	         order by g.giftID desc";
	$result = mysqli_query($conn, $query) or
	          log_fail('Cannot execute query: '.$query.' Error: '. mysqli_error($conn).'\n');

	while ($row = @mysqli_fetch_array($result, MYSQLI_ASSOC))
	{
		$rows[] = $row;
	}
	return $rows;
}

function getEmailCheckState($userid)
{
	$conn = connect();

	$query = "select sendHolidayEmails from USER where userID = ".$userid;
	$result = mysqli_query($conn, $query) or
	          log_fail('Cannot execute query: '.$query.' Error: '. mysqli_error($conn).'\n');

	while ($row = @mysqli_fetch_array($result, MYSQLI_ASSOC))
	{
		if ($row['sendHolidayEmails'] == 1)
		{
			return " checked";
		}
	}
	return "";
}

function updateEmailCheckState($userid, $state)
{
	$conn = connect();

	if ($state == "on")
	{
		$state = "1";
	}
	else
	{
		$state = "0";
	}

	$query = "update USER set sendHolidayEmails = ".$state." where userID = ".$userid;
	$result = mysqli_query($conn, $query) or
	          log_fail('Cannot execute query:'.$query.' Error: '.mysqli_error($conn));
	log_debug("updateEmailCheckState: uid:".$userid.", ".$state);
}

function getHolidays($userid, $format)
{
	$conn = connect();

	$query = "select * from USER_HOLIDAY uh, HOLIDAY h
	         where uh.holidayID = h.holidayID and userID = ".$userid;
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));

	$content = "";
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
	{
		$d = date_parse($row['holidayDate']);
		$content .= $d['month'].'/'.$d['day'].','
		            .$row['holidayDescription']."\n";
		if ($format == 'html')
		{
			$content .= '<br>';
		}
	}
	return $content;
}

function getUpcomingHolidaysList($userid)
{
	$conn = connect();

	$query = "select u.userID, h.holidayID, h.holidayDescription, h.holidayDate, e.sentOn,
	         dayofyear(h.holidayDate)-dayofyear(curdate()) as daysto
	         from USER_HOLIDAY u, USER usr, HOLIDAY h
	         left join EMAIL e on (h.holidayDescription = e.holidayDescription)
	         where u.holidayID = h.holidayID and u.userID = usr.userID
	         and usr.sendHolidayEmails = 1
	         and dayofyear(h.holidayDate) - dayofyear(curdate()) between 0 and 21
	         and (e.sentOn is null or e.sentOn < adddate(curdate(), -344))";

	if ($userid > 0)
	{
		$query .= " and u.userID = ".$userid;
	}

	$query .= " order by daysto";

	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));
	return $result;
}

function getNearestNotifications($userid)
{
	$result = getUpcomingHolidaysList($userid);

	$content = "";
	while ($row = mysql_fetch_array($result, MYSQLI_ASSOC))
	{
		$d = date_parse($row['holidayDate']);
		$sent = '';
		if ($row['sentOn'] > 0)
		{
			$sent = ', last sent on '.$row['sentOn'];
		}
		$content .= $d['month'].'/'.$d['day'].' '
		            .$row['holidayDescription'].' (in '.$row['daysto'].' days)'.$sent."<br>\n";
	}
	return $content;
}

function updateSentDate($userid, $holidayDescription)
{
	$conn = connect();

	$q = "select * from EMAIL where userID = ".$userid." and holidayDescription = '".$holidayDescription."'";
	$result = mysqli_query($conn, $q) or log_fail('updateSentDate: q:'.$q.', error:'.mysqli_error($conn));
	if ($row = mysqli_fetch_array($result))
	{
		$q = "update EMAIL set sentOn = curdate() where userID = ".$userid." and holidayDescription = '".$holidayDescription."'";
		mysqli_query($conn, $q) or log_fail ('updateSentDate: q:'.$q.', error:'.mysqli_error($conn));
	}
	else
	{
		$q = "insert into EMAIL(userID, holidayDescription, sentOn) values(".$userid.", '".$holidayDescription."', curdate())";
		mysqli_query($conn, $q) or log_fail ('updateSentDate: q:'.$q.', error:'.mysqli_error($conn));
	}
}

function sendHolidayEmails()
{
	log_info("sending holiday emails");

	$result = getUpcomingHolidaysList(0);
	$sent = 0;
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
	{
		log_info("userID: ".$row['userID'].", holidayID: ".$row['holidayID']);
		$email = createHolidayEmail($row['userID'], $row['holidayID'], $row['holidayDescription'], $row['holidayDate']);
		sendMail($email[0], $email[1], $email[2]);
		updateSentDate($row['userID'], $row['holidayDescription']);
		$sent++;
	}
	log_info("sending emails done");
	return "sent: ".$sent."\r\n";
}

function createHolidayEmail($userid, $holidayid, $holidayDescription, $holidayDate)
{
	$user = getUserInfo($userid);
	$date = date_parse($holidayDate);

	$conn = connect();

	$query = "select * from USER_FRIEND uf, FRIEND f where uf.friendID = f.friendID and uf.userID = ".$userid;
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));
	$friends = '';
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
	{
		$friends .= $row['email'].',';
	}

	$link = "http://".$_SERVER['HTTP_HOST'].'?fid='.$userid;
	$text = "Your friend ".$user['firstName']." ".$user['lastName']." has a holiday (".$holidayDescription.") at ".$date['month']."/".$date['day'].".\nSee his/her gift list: ".$link;

	return array($friends, "holiday soon", $text);
}

function addFriends($userid, $friends)
{
	$conn = connect();

	$lines = split("[\r\n]", $friends);
	$query = "delete from USER_FRIEND where userID = ".$userid;
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));

	foreach ($lines as $line)
	{
		if ($line != '')
		{
			$query = "insert into FRIEND(email) values ('".$line."')";
			$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));
			$query = 'SELECT LAST_INSERT_ID() FROM FRIEND';
			$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));
			$row = mysqli_fetch_array($result);
			$friendid = $row[0];
			$query = "insert into USER_FRIEND(userID, friendID) values (".$userid.",".$friendid.")";
			$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));
			log_debug("addFriends: uid:".$userid.", ".$line);
		}
	}
}

function createFriend($userid)
{
	$conn = connect();

	$query = "insert into FRIEND(email) values ('".$line."')";
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));
	$query = 'SELECT LAST_INSERT_ID() FROM FRIEND';
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	$friendid = $row[0];
	$query = "insert into USER_FRIEND(userID, friendID) values (".$userid.",".$friendid.")";
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));
	log_debug("createFriend: uid: ".$userid.", friendid: ".$friendid);
	return $friendid;
}

function addHolidays($userid, $friends)
{
	$conn = connect();

	$lines = split("[\r\n]", $friends);
	$query = "delete from USER_HOLIDAY where userID = ".$userid;
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));

	foreach ($lines as $line)
	{
		$cols = split(",", $line);
		if ($line != '')
		{
			$query = "insert into HOLIDAY(holidayDate, holidayDescription)
			         values ('1970/".$cols[0]."', '".$cols[1]."')";
			$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));
			$query = 'SELECT LAST_INSERT_ID() FROM HOLIDAY';
			$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));
			$row = mysqli_fetch_array($result);
			$holidayid = $row[0];
			$query = "insert into USER_HOLIDAY(userID, holidayID) values (".$userid.",".$holidayid.")";
			$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));
			log_debug("addHolidays: uid:".$userid.", ".$line);
		}
	}
}

function addGift($userid, $gift_name, $gift_url, $gift_picture, $gift_descr)
{
	$conn = connect();

	$query = "insert into GIFT(giftName, url, description, addingDate)
	         values ('".mysqli_real_escape_string($conn, substr($gift_name, 0, 200))."', '"
	         .mysqli_real_escape_string($conn, $gift_url)."', '"
	         .mysqli_real_escape_string($conn, $gift_descr)."', curdate())";
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query:'.$query.' Error: '.mysqli_error($conn));
	$query = 'SELECT LAST_INSERT_ID() FROM GIFT';
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));
	$row = mysqli_fetch_array($result);
	$giftid = $row[0];
	$query = "insert into USER_GIFT(userID, giftID) values (".$userid.",".$giftid.")";
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query '. mysqli_error($conn));

	if ($gift_picture != '')
	{
		$picdata = file_get_contents($gift_picture);
		file_put_contents('pics/'.$giftid.'.jpg', $picdata);
	}

	log_debug("addGift: uid:".$userid.", ".$gift);
}

function updateGift($userid, $giftid, $gift_descr)
{
	$conn = connect();

	$query = "update GIFT set description = '".mysqli_real_escape_string($conn, $gift_descr)."'
	         where giftID = ".$giftid;
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query:'.$query.' Error: '.mysqli_error($conn));
	log_debug("updateGift: uid:".$userid.", ".$giftid.", ".$gift_descr);
}

function deleteGift($userid, $giftid)
{
	$conn = connect();

	$query = "delete from USER_GIFT where userID = ".$userid." and giftID = ".$giftid;
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query "'.$query.'". Error:'. mysqli_error($conn));
	log_debug("deleteGift: uid:".$userid.", giftid:".$giftid);
}

function selectGift($giftid, $friendid)
{
	$conn = connect();

	$query = "update USER_GIFT set friendID = ".$friendid." where giftID = ".$giftid;
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query "'.$query.'". Error:'. mysqli_error($conn));
	log_debug("selectGift: friendid:".$friendid.", giftid:".$giftid);
}

function unselectGift($giftid)
{
	$conn = connect();

	$query = "update USER_GIFT set friendID = NULL where giftID = ".$giftid;
	$result = mysqli_query($conn, $query) or log_fail('Cannot execute query "'.$query.'". Error:'. mysqli_error($conn));
	log_debug("unselectGift: giftid:".$giftid);
}

function getBaseUrl($url)
{
	preg_match('!(http://[^/]*)!', $url, $m);
	return $m[1];
}

function getPicture($url, $text, $hints)
{
	log_debug("getPicture: url:".$url);
	if (preg_match("/amazon.com/", $url) > 0)
	{
		$r = preg_match_all("/<img[^>]*/", $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			if (preg_match('!src="(https://images-na.ssl-images-amazon.com/images/I/[^"]*)!', $m[0], $msrc) > 0)
			{
				return $msrc[1];
			}

			if (preg_match('!data-old-hires=\"(https://images-na.ssl-images-amazon.com/images/[^\"]*)\"!', $m[0], $msrc) > 0)
			{
				return $msrc[1];
			}

			if (preg_match('!data-old-hires=\"\" .*(https://images-na.ssl-images-amazon.com/images/I/.*.jpg)&quot;!', $m[0], $msrc) > 0)
			{
				return $msrc[1];
			}

			if (preg_match('!data-a-dynamic-image=.*(https://images-na.ssl-images-amazon.com/images/I/.*.jpg)&quot;!', $m[0], $msrc) > 0)
			{
				return $msrc[1];
			}
		}
	}
	elseif (preg_match("/victoriassecret.com/", $url) > 0)
	{
		$r = preg_match_all("/<img[^>]*/", $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			if (preg_match('!src="(http://media.victoriassecret.com/product/prod[^"]*)!', $m[0], $msrc) > 0)
			{
				return $msrc[1];
			}
		}
	}
	elseif (preg_match("/llbean.com/", $url) > 0)
	{
		$r = preg_match_all("/<img[^>]*/", $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			log_debug($m[0]);
			if (preg_match('!src="(//cdni.llbean.com/is/image/wim[^"]*)!', $m[0], $msrc) > 0)
			{
				return 'http:'.$msrc[1];
			}
		}
	}
	elseif (preg_match("/childrensplace.com/", $url) > 0)
	{
		$r = preg_match_all("/<img[^>]*/", $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			if (preg_match('!src="(http://childrensplace5.richfx.com.edgesuite.net/image/media[^"]*)!',
			               $m[0], $msrc) > 0)
			{
				return $msrc[1];
			}
		}
	}
	elseif (preg_match("/gap.com/", $url) > 0)
	{
		$r = preg_match_all("/<img[^>]*/", $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			if (preg_match('!src="(http://www[0-9].assets-gap.com/Asset_Archive/ONWeb/Assets/Product/[^"]*)!',
			               $m[0], $msrc) > 0)
			{
				print "msrc".$msrc;
				return $msrc[1];
			}
		}
	}
	elseif (preg_match("/carters.com/", $url) > 0)
	{
		$r = preg_match_all("/<link[^>]*/", $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			if (preg_match('!"(http:[^"]*.jpg)!i', $m[0], $msrc) > 0)
			{
				return $msrc[1];
			}
		}
	}
	elseif (preg_match("/toysrus.com/", $url) > 0)
	{
		$r = preg_match_all("/<img[^>]*/", $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			if (preg_match('!"(http://trus.imageg.net/graphics/product_images/[^"]*)!i',
			               $m[0], $msrc) > 0)
			{
				return $msrc[1];
			}
		}
	}
	elseif (preg_match("/dickssportinggoods.com/", $url) > 0)
	{
		$r = preg_match_all("/<img[^>]*/", $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			if (preg_match('!"(http://dsp.imageg.net/graphics/product_images/[^"]*w\.jpg)!i',
			               $m[0], $msrc) > 0)
			{
				return $msrc[1];
			}
		}
	}
	elseif (preg_match("/macys.com/", $url) > 0)
	{
		$r = preg_match_all("/<img[^>]*/", $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			if (preg_match('!"(http://slimages.macys.com/is/image/MCY/products/[^"]*)!i',
			               $m[0], $msrc) > 0)
			{
				return $msrc[1];
			}
		}
	}
	elseif (preg_match("/fredericks.com/", $url) > 0)
	{
		$r = preg_match_all("/<img[^>]*/", $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			if (preg_match('!"(http://demandware.edgesuite.net/aacj_prd/on/demandware.static/[^"]*lrg\.jpg)!i',
			               $m[0], $msrc) > 0)
			{
				return $msrc[1];
			}
		}
	}
	elseif (preg_match("/borders.com/", $url) > 0)
	{
		$r = preg_match_all("/<img[^>]*/", $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			if (preg_match('!"(http://www.borders.com/ProductImages/products/[^"]*)!i',
			               $m[0], $msrc) > 0)
			{
				return $msrc[1];
			}
		}
	}
	elseif (preg_match("/jr.com/", $url) > 0)
	{
		$r = preg_match_all("/<img[^>]*/", $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			if (preg_match('!"(http://images.jr.com/productimages/[^"]*)!i',
			               $m[0], $msrc) > 0)
			{
				return $msrc[1];
			}
		}
	}
	elseif (preg_match("/carpatina.com/", $url) > 0)
	{
		$r = preg_match_all("/<img[^>]*/", $text, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			if (preg_match('!"(productimages_new/[^"]*)!i',
			               $m[0], $msrc) > 0)
			{
				return 'http://carpatina.com/'.$msrc[1];
			}
		}
	}

	$r = preg_match_all("!<img[^>]*!", $text, $matches, PREG_SET_ORDER);
	$maxhint = 0;
	foreach ($matches as $m)
	{
		$hintcount = 0;
		if (preg_match('!src=[\'"]([^\'"]*\.jpg.*)!i', $m[0], $msrc) > 0)
		{
			$u = $msrc[1];
			$first = $u;

			foreach ($hints as $hint)
			{
				if (stripos($u, $hint) === false)
					continue;

				$hintcount++;
			}

			if ($hintcount > $maxhint)
			{
				$picurl = $u;
				$maxhint = $hintcount;
			}
		}
	}

	if (preg_match('!^/!', $picurl))
		$picurl = getBaseUrl($url).$picurl;

	if ($picurl == '')
		$picurl = $first;

	return $picurl;
}

function getTitle($html)
{
	preg_match("/<title>(.*)<\/title>/msU", $html, $m);
	$s = preg_replace('[\"\']', '', $m[1]);
	return $s;
}

function parseGiftInfo($url)
{
	$text = @file_get_contents($url);
	echo $text;
	if ($text === false)
	{
		return array('cannot get title', 'cannot get picture');
	}
	$title = getTitle($text);
	$picture = getPicture($url, $text, explode(' ', $title));
	log_debug('$picture:'.$picture);
	return array($title, $picture);
}

function test()
{
	$url = 'https://www.amazon.com/First-Disney-Frozen-Guitar-Ukulele/dp/B01DPISLCW/ref=sr_1_2?ie=UTF8&qid=1481379628&sr=8-2-spons&keywords=guitar&psc=1';
	print_r(parseGiftInfo($url));
}

//test();

?>

