<html>
<head>
<title>Hiking in Colorado</title>
</head>

<body>

<center>
<h1>Hiking the Rocky Mountains!</h1>
</center>

<i>
Hiking is, I would say, one of the central activities of life
in the Rockies.  And it doesn't stop in winter.  The seasons
here are less distinct, I'd say, than the drastic changes that
happen in coastal areas.  It's not a cold experience like some
would imagine; just bring snowshoes and maybe crampons!
And since we live in a part
of the country that is just amazingly beautiful, there is a lot
of awesome mountain wilderness to explore.
</i>
<p>

<?php

$fp = fopen("hike_list", "r");

$links = FALSE;
while (TRUE)
{
    $link = trim(fgets($fp));
    $href = trim(fgets($fp));
    $thumb = trim(fgets($fp));

    if ($link == FALSE || $href == $FALSE || $thumb == FALSE)
    {
        break;
    }

    $links[] = $link;
    $hrefs[] = $href;
    $thumbs[] = $thumb;
}

fclose($fp);

if ($links != FALSE)
{
    print "<table cellspacing=10>\n";
    for ($i = count($links) - 1; $i >= 0; --$i)
    {
        print "<tr>\n";
        print "<td><a href=\"$hrefs[$i]\"><img border=0 src=\"$thumbs[$i]\"></a></td>\n";
        print "<td valign=bottom><b><a href=\"$hrefs[$i]\">$links[$i]</a></b></td>\n";
        print "</tr>\n";
    }
}

?>

<table width=100%>
<tr>
<td align=right><i><a href="http://www.skyrush.com/">skyrush.com</a></i></td>
</tr>
</table>

</body>
</html>
