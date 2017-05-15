<?php if ($action == "add_holiday") { ?>
<b>My holidays</b> <a class="plus" href="?">[-]</a>
<div style="padding: 4px; width: 500px; height: 320px; border: solid 2px #EE5599; position: absolute; background-color: #FFCCEE">
	<form method="POST" id="add_holiday_form" action="?uid=<?php echo $userid ?>&action=submit_holidays">
		<b>Edit holidays:</b><br>Enter one holiday per line<br>format: month/day,description<br>Example: 1/1,new year<br>
		<textarea id="addholiday_box" class="ib" name="addholiday_box" style="width: 380px; height: 240px"><?php echo getHolidays($userid, "text") ?></textarea>
		<br>
		<input class="sb" type="submit" name="add" value="OK">
	</form>
</div>
<?php } else { ?>
<b>My holidays</b> <a title="edit all Holidays" class="plus" href="?action=add_holiday">[+]</a><br>
<?php } ?>
<?php echo getHolidays($userid, "html") ?>
<br>
<br>
