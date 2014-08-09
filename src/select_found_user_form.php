<?
if (count($found_users) > 0)
{
?>
<hr class="sep">
<b>Multiple entries found. Select your friend or search again:</b><br><br>
<?
foreach ($found_users as $row)
{
	if (file_exists('photos/'.$row['userID'].'.jpg'))
		$photo = 'trunk/photos/'.$row['userID'].'.jpg';
    else
        $photo = '';
?>

<a class="add" href="?fid=<? echo $row['userID'] ?>">
<? if ($photo != '') { ?>
<img style="vertical-align:middle; border: solid 0px" width="60px" src="<? echo $photo ?>">
<? } ?>
<? echo $row['firstName'] ?> <? echo $row['lastName'] ?>
</a>
<br>
<br>
<?
}
}
else
{
?>&nbsp;<b>No entries found</b><?
}
 ?>
<br>
