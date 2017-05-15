<?php
if (count($found_users) > 0)
{
?>
<hr class="sep">
<b>Multiple entries found. Select your friend or search again:</b><br><br>
<?php
foreach ($found_users as $row)
{
	if (file_exists('photos/'.$row['userID'].'.jpg'))
		$photo = 'trunk/photos/'.$row['userID'].'.jpg';
    else
        $photo = '';
?>

<a class="add" href="?fid=<?php echo $row['userID'] ?>">
<?php if ($photo != '') { ?>
<img style="vertical-align:middle; border: solid 0px" width="60px" src="<?php echo $photo ?>">
<?php } ?>
<?php echo $row['firstName'] ?> <?php echo $row['lastName'] ?>
</a>
<br>
<br>
<?php
}
}
else
{
?>&nbsp;<b>No entries found</b><?php
}
?>
<br>
