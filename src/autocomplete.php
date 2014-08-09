<?php
include('gifts_db.php');

connect();

$q = $_GET['q'];
$query = "select userID, firstName, lastName from USER where firstName like '".$q."%' or lastName like '".$q."%'";

$result = mysql_query($query) or log_fail ('Autocomplete: '. mysql_error().'\n');

$text = '';
while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
{
	$text .= '<option value="'.$row['userID'].'">'.$row['firstName'].' '.$row['lastName'].'</option>\n';
}

$text .= '<option value="0">------------</option>\n';

if ($text != '')
{
	echo '<select id="selector" style="width:200px; border: solid 0px gray;" multiple="multiple" size="10" border="0" onclick="selector_onclick()" onkeydown="selector_keydown(event)">';
	echo $text;
	echo "</select>\n";
}
?>

