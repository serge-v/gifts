<? if ($action == "add_friend") { ?>
<b>My friends</b> <a class="plus" href="?uid=<? echo $userid ?>">[-]</a>
<div style="width: 400px; height: 300px; position: absolute;">
	<form method="POST" id="add_friend_form" action="?action=submit_friends">
		<b>Edit friends:</b><br>
		<textarea id="addfriend_box" class="ib" name="addfriend_box" style="width: 380px; height: 240px"><? echo getFriends($userid, "") ?></textarea>
		<br>
		<input class="sb" type="submit" name="add" value="OK">
	</form>
</div>
<? } else { ?>
<b>My friends</b> <a class="plus" href="?action=add_friend">[+]</a><br>
<? } ?>
<? echo getFriends($userid, "") ?>
<br>
<br>
