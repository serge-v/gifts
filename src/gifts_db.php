<?php

function log_add($s, $type)
{
    $f = fopen('log.txt', 'a');
    fwrite($f, date('Y-m-d H:i:s').' '.$type.' '.$s."\n");
    fclose($f);
}

function log_info($s)  { log_add($s, 'INFO '); }
function log_debug($s) { log_add($s, 'DEBUG'); }
function log_error($s) { log_add($s, 'ERROR'); }
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
    if ($_SERVER['SERVER_ADDR'] == '192.168.1.8')
    {
        include "config_dev.php";
    }
    else
    {
        include "config_prod.php";
    }
    mysql_connect($dbhost, $dbuser, $dbpass) or log_fail('Error connecting to mysql: '.mysql_error());
    mysql_select_db($dbname) or log_fail('Could not select database: '.mysql_error());
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
    connect();
    
    $password = createPassword(8);
    $query = "select userID from USER where userEmail = '".$username."'";
    $result = mysql_query($query) or log_fail ('createUser (select): '. mysql_error());
    if ($row = mysql_fetch_array($result))
    {
        if ($userfname != '')
        {
            $add_info = ", firstName = '".mysql_real_escape_string($userfname)."'";
        }
        if ($userlname != '')
        {
            $add_info .= ", lastName = '".mysql_real_escape_string($userlname)."'";
        }
        
        $query = "update USER set password = '".$password."' ".$add_info." where userID = ".$row[0];
        mysql_query($query) or log_fail ('createUser (update): query:'.$query.', error:'.mysql_error());
        log_debug('user already exists: '.$username.' '.$query);
        return array('', $password);
    }
    else
    {
        $query = "insert into USER(userEmail, password, firstName, lastName) 
            values ('".$username."','".$password."', '".mysql_real_escape_string($userfname)."', 
            '".mysql_real_escape_string($userlname)."')";
        
        if ($result = mysql_query($query))
        {
            $query = 'SELECT LAST_INSERT_ID() FROM USER';
            $result = mysql_query($query) or log_fail ('createUser (insert): '. mysql_error());
            $row = mysql_fetch_array($result);
            $userid = $row[0];
            log_info('createUser: '.$userid);
        }
        else
        {
            $error = 'Cannot create user. Reason: '. mysql_error();
        }
    }
    
    return array($error, $password);
}

function updatePassword($userid, $currpassword, $newpassword)
{
    connect();

    $q = "select * from USER where userID = ".$userid;
    $result = mysql_query($q) or log_fail('updatePassword: q:'.$q.', error:'.mysql_error());
    if ($row = mysql_fetch_array($result))
    {
        if ($row['password'] != $currpassword)
        {
            return 'Current password is invalid';
        }
        $q = "update USER set password = '".mysql_real_escape_string($newpassword)."' where userID = ".$userid;
        mysql_query($q) or log_fail ('updatePassword: q:'.$q.', error:'.mysql_error());
    }
    else
    {
        return 'Cannot find user';
    }
}

function updateSettings($userid, $userfname, $userlname, $userphoto)
{
    connect();
    
    $query = "update USER set ".
        "  firstName = '".mysql_real_escape_string($userfname)."'".
        ", lastName = '".mysql_real_escape_string($userlname)."'".
        " where userID = ".$userid;
     mysql_query($query) or 
        log_fail ('updateSettings: query:'.$query.', error:'.mysql_error());

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
    connect();
    
    $query = "SELECT userID FROM USER where userEmail = '".$username."' AND password = '".$password."'";
    $result = mysql_query($query) or log_fail ('userLogin: '. mysql_error());
    if ($row = mysql_fetch_array($result))
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
    connect();
    
    $query = "select * from USER u where userid = ".$userid;
    $result = mysql_query($query) or log_fail('getUserInfo: '.$query.'. Error: '. mysql_error());
    $row = mysql_fetch_array($result);
    
    return $row;
}

function findUser($find_info)
{
    connect();

    $query = "select userID, firstName, lastName from USER 
        where userEmail like '%".$find_info."%' 
        or firstName like '%".$find_info."%' 
        or lastName like '%".$find_info."%'";
    $result = mysql_query($query) or log_fail('findUser: '. mysql_error());
    while ($row = @mysql_fetch_array($result, MYSQL_ASSOC))
    {
        $rows[] = $row;
    }
    return $rows;
}

function getFriends($userid, $separator)
{
    connect();
    
    $query = "select * from USER_FRIEND uf, FRIEND f where uf.friendID = f.friendID and userID = ".$userid;
    $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error().'\n');
    
    $content = "";
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
    {
        $content .= $row['email'].$separator."\n";
    }
    return $content;
}

function getGifts($userid)
{
    connect();
    
    $query = "select * from USER_GIFT ug, GIFT g 
        where ug.giftID = g.giftID and userID = ".$userid." 
        order by g.giftID desc";
    $result = mysql_query($query) or 
        log_fail('Cannot execute query: '.$query.' Error: '. mysql_error().'\n');
    
    while ($row = @mysql_fetch_array($result, MYSQL_ASSOC))
    {
        $rows[] = $row;
    }
    return $rows;
}

function getEmailCheckState($userid)
{
    connect();
    
    $query = "select sendHolidayEmails from USER where userID = ".$userid;
    $result = mysql_query($query) or 
        log_fail('Cannot execute query: '.$query.' Error: '. mysql_error().'\n');
    
    while ($row = @mysql_fetch_array($result, MYSQL_ASSOC))
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
    connect();

    if ($state == "on")
    {
        $state = "1";
    }
    else
    {
		$state = "0";
	}
    
    $query = "update USER set sendHolidayEmails = ".$state." where userID = ".$userid;
    $result = mysql_query($query) or 
        log_fail('Cannot execute query:'.$query.' Error: '.mysql_error());
    log_debug("updateEmailCheckState: uid:".$userid.", ".$state);
}

function getHolidays($userid, $format)
{
    connect();
    
    $query = "select * from USER_HOLIDAY uh, HOLIDAY h 
        where uh.holidayID = h.holidayID and userID = ".$userid;
    $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
    
    $content = "";
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
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
	connect();
	
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

    $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
    return $result;
}

function getNearestNotifications($userid)
{
	$result = getUpcomingHolidaysList($userid);

    $content = "";
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
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
    connect();

    $q = "select * from EMAIL where userID = ".$userid." and holidayDescription = '".$holidayDescription."'";
    $result = mysql_query($q) or log_fail('updateSentDate: q:'.$q.', error:'.mysql_error());
    if ($row = mysql_fetch_array($result))
    {
        $q = "update EMAIL set sentOn = curdate() where userID = ".$userid." and holidayDescription = '".$holidayDescription."'";
        mysql_query($q) or log_fail ('updateSentDate: q:'.$q.', error:'.mysql_error());
    }
    else
    {
        $q = "insert into EMAIL(userID, holidayDescription, sentOn) values(".$userid.", '".$holidayDescription."', curdate())";
        mysql_query($q) or log_fail ('updateSentDate: q:'.$q.', error:'.mysql_error());
    }
}

function sendHolidayEmails()
{
	log_info("sending holiday emails");
	
	$result = getUpcomingHolidaysList(0);
	$sent = 0;
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
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
	
    connect();

    $query = "select * from USER_FRIEND uf, FRIEND f where uf.friendID = f.friendID and uf.userID = ".$userid;
    $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
    $friends = ''; 
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
    {
        $friends .= $row['email'].',';
    }

    $link = "http://".$_SERVER['HTTP_HOST'].'?fid='.$userid;
    $text = "Your friend ".$user['firstName']." ".$user['lastName']." has a holiday (".$holidayDescription.") at ".$date['month']."/".$date['day'].".\nSee his/her gift list: ".$link;

    return array($friends, "holiday soon", $text);
}

function addFriends($userid, $friends)
{
    connect();
    
    $lines = split("[\r\n]", $friends);
    $query = "delete from USER_FRIEND where userID = ".$userid;
    $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
    
    foreach ($lines as $line)
    {
        if ($line != '')
        {
            $query = "insert into FRIEND(email) values ('".$line."')";
            $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
            $query = 'SELECT LAST_INSERT_ID() FROM FRIEND';
            $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
            $row = mysql_fetch_array($result);
            $friendid = $row[0];        
            $query = "insert into USER_FRIEND(userID, friendID) values (".$userid.",".$friendid.")";
            $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
            log_debug("addFriends: uid:".$userid.", ".$line);
        }
    }
}

function createFriend($userid)
{
    connect();
    
    $query = "insert into FRIEND(email) values ('".$line."')";
    $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
    $query = 'SELECT LAST_INSERT_ID() FROM FRIEND';
    $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
    $row = mysql_fetch_array($result);
    $friendid = $row[0];        
    $query = "insert into USER_FRIEND(userID, friendID) values (".$userid.",".$friendid.")";
    $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
    log_debug("createFriend: uid: ".$userid.", friendid: ".$friendid);
    return $friendid;
}

function addHolidays($userid, $friends)
{
    connect();
    
    $lines = split("[\r\n]", $friends);
    $query = "delete from USER_HOLIDAY where userID = ".$userid;
    $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
    
    foreach ($lines as $line)
    {
        $cols = split(",", $line);
        if ($line != '')
        {
            $query = "insert into HOLIDAY(holidayDate, holidayDescription) 
                values ('1970/".$cols[0]."', '".$cols[1]."')";
            $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
            $query = 'SELECT LAST_INSERT_ID() FROM HOLIDAY';
            $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
            $row = mysql_fetch_array($result);
            $holidayid = $row[0];       
            $query = "insert into USER_HOLIDAY(userID, holidayID) values (".$userid.",".$holidayid.")";
            $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
            log_debug("addHolidays: uid:".$userid.", ".$line);
        }
    }
}

function addGift($userid, $gift_name, $gift_url, $gift_picture, $gift_descr)
{
    connect();
    
    $query = "insert into GIFT(giftName, url, description) 
        values ('".mysql_real_escape_string($gift_name)."', '"
        .mysql_real_escape_string($gift_url)."', '"
        .mysql_real_escape_string($gift_descr)."')";
    $result = mysql_query($query) or log_fail('Cannot execute query:'.$query.' Error: '.mysql_error());
    $query = 'SELECT LAST_INSERT_ID() FROM GIFT';
    $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());
    $row = mysql_fetch_array($result);
    $giftid = $row[0];      
    $query = "insert into USER_GIFT(userID, giftID) values (".$userid.",".$giftid.")";
    $result = mysql_query($query) or log_fail('Cannot execute query '. mysql_error());

    if ($gift_picture != '')
    {
        $picdata = file_get_contents($gift_picture);
        file_put_contents('pics/'.$giftid.'.jpg', $picdata);
    }

    log_debug("addGift: uid:".$userid.", ".$gift);
}

function updateGift($userid, $giftid, $gift_descr)
{
    connect();
    
    $query = "update GIFT set description = '".mysql_real_escape_string($gift_descr)."' 
        where giftID = ".$giftid;
    $result = mysql_query($query) or log_fail('Cannot execute query:'.$query.' Error: '.mysql_error());
    log_debug("updateGift: uid:".$userid.", ".$giftid.", ".$gift_descr);
}

function deleteGift($userid, $giftid)
{
    connect();
    
    $query = "delete from USER_GIFT where userID = ".$userid." and giftID = ".$giftid;
    $result = mysql_query($query) or log_fail('Cannot execute query "'.$query.'". Error:'. mysql_error());
    log_debug("deleteGift: uid:".$userid.", giftid:".$giftid);
}

function selectGift($giftid, $friendid)
{
    connect();
    
    $query = "update USER_GIFT set friendID = ".$friendid." where giftID = ".$giftid;
    $result = mysql_query($query) or log_fail('Cannot execute query "'.$query.'". Error:'. mysql_error());
    log_debug("selectGift: friendid:".$friendid.", giftid:".$giftid);
}

function unselectGift($giftid)
{
    connect();
    
    $query = "update USER_GIFT set friendID = NULL where giftID = ".$giftid;
    $result = mysql_query($query) or log_fail('Cannot execute query "'.$query.'". Error:'. mysql_error());
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
            if (preg_match('!src="(http://ecx.images-amazon.com/images/I/[^"]*)!', $m[0], $msrc) > 0)
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
    if (strpos($url, 'http://') === false)
    {
        $title = $url;
    }
    else
    {
        $text = @file_get_contents($url);
        if ($text === false)
        {
            return array('cannot get title', 'cannot get picture');
        }
        $title = getTitle($text);
        $picture = getPicture($url, $text, explode(' ', $title));
        log_debug('$picture:'.$picture);
    }
    return array($title, $picture);
}

function test()
{
	$url = 'http://oldnavy.gap.com/browse/product.do?cid=73355&vid=1&pid=885867&scid=885867012';
	print_r(parseGiftInfo($url));
}

?>
