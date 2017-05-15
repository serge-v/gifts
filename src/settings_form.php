<?php
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
	<?php echo $info['userEmail'] ?>
	<br><br>
	<span class="w1">First name:</span>
	<input style="width: 250px" class="ib" 
		type="text" name="userfname" value="<?php echo $info['firstName'] ?>"/>
	<br><br>
	<span class="w1">Last name:</span>
	<input style="width: 250px" class="ib" 
		type="text" name="userlname" value="<?php echo $info['lastName'] ?>"/>
	<br><br>
	<span class="w1">Photo:</span>
	<?php if ($photo != '') { echo '<img width="120px" src="'.$photo.'">'; } ?>
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
<?php
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
<script>
function setscroll(id)
{
	var top = document.documentElement.scrollTop || document.body.scrollTop;
	var el = document.getElementById(id);
	el.value = top;
}
</script>
<form method="POST" id="add_friend_form" action="?action=submit_friends" onsubmit="setscroll('scroll_friends');">
	<span class="w1"></span>
	<textarea id="addfriend_box" class="ib" name="addfriend_box" 
        style="width: 250px; height: 240px"><?php echo getFriends($userid, "") ?></textarea>
	<br/>
	<br/>
	<span class="w1"></span>
	<input class="sb" type="submit" name="save" value="save" />
	<input id="scroll_friends" type="hidden" name="scroll" value="0" />
</form>
<br>
 
<a name="h"/>
<hr class="sep">
<b>Holidays:</b><br><br>
<form method="POST" id="add_holiday_form" action="?action=submit_holidays" onsubmit="setscroll('scroll_holiday');">
	<span class="w1"></span>
	One holiday per line (Example: 1/1,New Year)<br>
	<span class="w1"></span>
	<textarea id="addholiday_box" class="ib" name="addholiday_box" 
        style="width: 250px; height: 240px"><?php echo getHolidays($userid, "text") ?></textarea>
	<br>
	<br>
	<span class="w1"></span>
	<input class="sb" type="submit" name="save" value="save">
	<input id="scroll_holiday" type="hidden" name="scroll" value="0" />
</form>
<br>

<a name="n"/>
<hr class="sep">
<b>Reminders:</b><br><br>
<form method="POST" id="email_options_form" action="?action=submit_email_options" onsubmit="setscroll('scroll_email');">
	<span class="w1"></span>
	<input type="checkbox" name="send_email_check" <?php echo getEmailCheckState($userid) ?>>
	Remind friends by email three weeks before holiday
	<br>
	<br>
	<span class="w1"></span>
	<input class="sb" type="submit" name="save" value="save">
	<input id="scroll_email" type="hidden" name="scroll" value="0" />
</form>
<b>Upcoming emails:</b><br><br>
<?php echo getNearestNotifications($userid) ?>
<br>

<hr class="sep">
<b>Go back to the main page:</b><br><br>
<form method="POST" enctype="multipart/form-data" action="?">
	<input class="sb" type="submit" name="return" value="Return"/>
</form>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>

