<?
$info = getUserInfo($userid);
if (file_exists('photos/'.$userid.'.jpg'))
{
	$photo = 'photos/'.$userid.'.jpg';
}
?>
<hr class="sep">
<b>Settings:</b><br><br>
<form method="POST" enctype="multipart/form-data" action="?action=save_settings">
	<input type="hidden" name="MAX_FILE_SIZE" value="500000" />
	<span class="w1">Email:</span>
	<? echo $info['userEmail'] ?>
	<br><br>
	<span class="w1">First name:</span>
	<input style="width: 250px" class="ib" 
		type="text" name="userfname" value="<? echo $info['firstName'] ?>"/>
	<br><br>
	<span class="w1">Last name:</span>
	<input style="width: 250px" class="ib" 
		type="text" name="userlname" value="<? echo $info['lastName'] ?>"/>
	<br><br>
	<span class="w1">Photo:</span>
	<? if ($photo != '') { echo '<img width="120px" src="'.$photo.'">'; } ?>
	<br><br>
	<span class="w1">New photo:</span>
	<input style="width: 250px;" class="ib" type="file" name="userphoto" value=""/>
	<br><br>
	<span class="w1"></span>
	<input class="sb" type="submit" name="save" value="Save"/>
</form>
<br>
<hr class="sep">
<b>Change password:</b><br><br>
<?
if ($error == 'ok')
{
    echo '<span class="pwdok">Password saved</span><br><br>';
}
?>
<form method="POST" enctype="multipart/form-data" action="?action=change_password">
	<span class="w1">Current password:</span>
	<input style="width: 250px" class="ib" type="text" name="currpassword"/>
	<br><br>
	<span class="w1">New password:</span>
	<input style="width: 250px" class="ib" type="text" name="newpassword"/>
	<br><br>
	<span class="w1">Confirm new password:</span>
	<input style="width: 250px" class="ib" type="text" name="newpasswordc"/>
	<br><br>
	<span class="w1"></span>
	<input class="sb" type="submit" name="save" value="Save"/>
</form>
<br>

<a name="f"/>
<hr class="sep">
<b>Friends:</b><br><br>
<form method="POST" id="add_friend_form" action="?action=submit_friends">
	<span class="w1"></span>
	<textarea id="addfriend_box" class="ib" name="addfriend_box" 
        style="width: 250px; height: 240px"><? echo getFriends($userid, "") ?></textarea>
    <br>
	<br>
	<span class="w1"></span>
	<input class="sb" type="submit" name="save" value="save">
</form>
<br>

<a name="h"/>
<hr class="sep">
<b>Holidays:</b><br><br>
<form method="POST" id="add_holiday_form" action="?action=submit_holidays">
	<span class="w1"></span>
    One holiday per line (Example: 1/1,New Year)<br>
	<span class="w1"></span>
	<textarea id="addholiday_box" class="ib" name="addholiday_box" 
        style="width: 250px; height: 240px"><? echo getHolidays($userid, "text") ?></textarea>
    <br>
    <br>
	<span class="w1"></span>
	<input class="sb" type="submit" name="save" value="save">
</form>
<br>

<a name="n"/>
<hr class="sep">
<b>Reminders:</b><br><br>
<form method="POST" id="email_options_form" action="?action=submit_email_options">
	<span class="w1"></span>
    <input type="checkbox" name="send_email_check" <? echo getEmailCheckState($userid) ?>>
    Remind friends by email three weeks before holiday
    <br>
    <br>
	<span class="w1"></span>
	<input class="sb" type="submit" name="save" value="save">
</form>
<b>Upcoming emails:</b><br><br>
<? echo getNearestNotifications($userid) ?>
<br>

<hr class="sep">
<b>Go back to the main page:</b><br><br>
<form method="POST" enctype="multipart/form-data" action="?">
	<input class="sb" type="submit" name="return" value="Return"/>
</form>
<br>
