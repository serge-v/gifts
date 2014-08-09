<hr class="sep">
<b>Find friend's gift list:</b><br><br>
<form method="GET" id="search" action="?action=find_user" autocomplete="off">
	<input type="hidden" name="fid" id="fid">
    <span class="w1">Friend name:</span>
	<input style="width: 180px" type="text" class="ib" 
        name="q" id="text" onkeyup="javascript:processkey(event);void(0)">
    <div id="suggestbox" class="suggbox" style="display: none; left: 130px;"></div>
    <input class="sb" type="submit" value="Find"/>
</form>
<br>
<script>
document.getElementById('text').focus();
</script>

