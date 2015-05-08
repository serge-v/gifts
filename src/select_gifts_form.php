<?
$userInfo = getUserInfo($friendid);
?>
<hr class="sep">
<img class="gr" src="/photos/<?=$userInfo['userID']?>.jpg"><br>
<b>Gifts that your friend <? echo $userInfo['firstName'] ?> <? echo $userInfo['lastName'] ?> wish to receive</b><br>
Click <span class="add">[select]</span> to mark item selected by you
<table>
<tr>
<th>&nbsp;</th>
<th></th>
</tr>
<? $rows = getGifts($friendid);
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
        <? if (file_exists($imgpath)) { ?>
        <a target="_blank" href="<? echo $row['url'] ?>"><img class="gr" src="<?=$imgurl?>"/></a>
         <? } ?>
    </td>
    <td width="300px" class="gr">
        <?=$name?>
        <br>
        <span class="descr">&nbsp;&nbsp;<?=$desc?>&nbsp;&nbsp;</span>
        <br><br><br>
        <span class="add">
        <? if ($row['friendID'] == $viewerid) { ?>
        you selected 
        <a class="addg" title="unselect gift" class="plus" 
            href="?action=unselect_gift&giftid=<?=$id?>&fid=<? echo $friendid ?>"><b>[unselect]</b></a>
        <? } else if ($row['friendID'] > 0) { ?>
        somebody selected
        <? } else { ?>
        nobody selected yet <a class="add" title="select gift" class="plus"
            href="?action=select_gift&giftid=<?=$id?>&fid=<? echo $friendid ?>"><b>[select]</b></a>
        </span>
        <? } ?>
    </td>
</tr>
<? } ?>
</table>
<br>
<br>
