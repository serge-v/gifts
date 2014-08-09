<?php

include 'gifts_db.php';

$url = 'http://carpatina.com/proddetail.asp?prod=K0000';

$text = file_get_contents('contents.txt');
if ($text == '')
{
    $text = file_get_contents($url);
    file_put_contents('contents.txt', $text);
}

$title = getTitle($text);
echo 'len:'.strlen($text)."\n";
echo 'title:'.$title."\n";
$picture = getPicture($url, $text, explode(' ', $title));
echo 'picurl:'.$picture."\n";

?>

