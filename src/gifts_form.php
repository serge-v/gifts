<script>
function paste_gift()
{
	document.getElementById('paste_gift_form').submit();
}

function show_update_box(id)
{
	var ea = document.getElementById('ea' + id);
	var ed = document.getElementById('ed' + id);
	var edd = document.getElementById('edd' + id);
	ea.style.display = 'none';
	ed.style.display = '';
	edd.focus();
}
</script>

<hr class="sep">

<br><b>Add a gift</b><br><br>

<?php if ($action == "add_gift") {
      if ($gift_name == '') $gift_name = "enter name on this line (replace all text);";
      if ($gift_url == '') $gift_url = "this line should contain hyperlink to the gift";

      log_debug('$gift_name='.$gift_name);

?>

<div class="form" style="width: 600px;">
	<form method="POST" id="add_gift_form" action="?action=submit_gift">
		Title:<br>
		<input id="addgift_box" class="ib" name="addgift_box"
			style="width: 580px" value="<?php echo $gift_name ?>">
		<br><br>
		Image Location:
		<br>
		<input class="ib" name="gift_picture"
			style="width: 580px" value="<?php echo $gift_picture ?>">
		<br><br>
        <img height="120px" src="<?php echo $gift_picture ?>"/>
		<br>
		<br>
		<input class="sb" type="submit" name="add" value="Add">
		<input class="sb" type="submit" name="cancel" value="Cancel">
		<input type="hidden" name="gift_url" value="<?php echo $gift_url ?>">
	</form>
</div>
<hr class="sep">
<script>
var a = document.getElementById('addgift_box');
a.focus();
</script>

<?php } else { ?>

<form method="POST" id="paste_gift_form" action="?action=paste_gift">
    <input type="text" name="gift_url" id="addlink" class="ib" style="width: 400px; color: gray"
	    onfocus="javascript:document.getElementById('addlink').value=''"
    	onchange="javascript:paste_gift()" value="paste URL or name here and press ENTER" />
</form>
<br>
<?php } ?>

<b>Gift list</b><br>
(Copy and send this link to friends: <b>http://<?=$_SERVER['HTTP_HOST'] ?>/?fid=<?=$userid ?></b>)
<br><br>
<table>
<tr>
<th width="300px">&nbsp;</th>
<th>&nbsp;</th>
</tr>
<?php $rows = getGifts($userid);
	if (count($rows) > 0)
   	foreach ($rows as $row)
	{
        $id = $row['giftID'];
        $imgpath = 'pics/'.$id.'.jpg';
        $imgurl = '/pics/'.$id.'.jpg';
        $name = $row['giftName'];
        $desc = $row['description'];
?>
	<tr>
		<td width="100px" class="gr">
            <?php if (file_exists($imgpath)) { ?>
			<a target="_blank" href="<?=$row['url']?>"><img class="gr" src="<?=$imgurl?>"/></a>
            <?php } ?>
		</td>
		<td width="300px" class="gr">
			<?=$name?>
            <br>
			<div id="e<?=$id?>">
				<a id="ea<?=$id?>" class="add" href="javascript:show_update_box('<?=$id?>');void(0)">
					[edit info] :&nbsp;<?=$desc?>
				</a>
				<div id="ed<?=$id?>" style="display: none; position: absolute;">
					<form method="POST" id="update_descr_form" action="?action=update_descr">
						<input class="sb" type="submit" value="set">
						<input id="edd<?=$id?>" type="text" name="gift_descr"
							class="ib" style="width: 400px; color: gray"
							value="<?=$desc?>" />
						<input type="hidden" name="giftid" value="<?=$id?>">
					</form>
					<a title="delete <?=$name?>" class="add" href="?action=delete_gift&giftid=<?=$id?>"><b>[delete gift]</b></a>
				</div>
			</div>
		</td>
	</tr>
<?php } ?>
</table>
<br>
<br>
