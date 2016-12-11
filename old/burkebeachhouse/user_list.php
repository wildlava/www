<?php

if ((include 'header.php') == FALSE)
{
    return;
}

$priv = $_SESSION['priv'];

print "<input type=\"button\" onClick=\"self.close();\" value=\"Close Window\"><p>\n";

if (strstr($priv, "r"))
{
    print "<font color=#FF0000><b>User list not available for read-only accounts</b></font>\n";
    return;
}

$conn = mysql_connect("localhost", $db_user, $db_pass);

print "<table border bordercolor=\"#dddddd\" bgcolor=\"#dddddd\" cellpadding=5 cellspacing=1>\n";
print "<tr><th>Name</th><th>Email</th><th>Phone number</th><th>Type</th></tr>\n";

$query = "SELECT * FROM Users ORDER BY name";
$result = mysql_db_query($db_name, $query, $conn);
while ($data = mysql_fetch_array($result, MYSQL_ASSOC))
{
    if (!is_array($data))
    {
        break;
    }
    
    $list_name = $data['name'];
//    $list_userid = $data['userid'];
    $list_email = $data['email'];
    $list_phone = $data['phone'];
    $list_color = $data['color'];
    $list_priv = $data['priv'];

    if (strstr($list_priv, "a"))
    {
        $list_status = "Administrator";
    }
    else if (strstr($list_priv, "p"))
    {
        $list_status = "Power User";
    }
    else if (strstr($list_priv, "r"))
    {
        $list_status = "Read-only User";
    }
    else
    {
        $list_status = "User";
    }
        
    if (strstr($list_priv, "r"))
    {
        print "<tr><td>$list_name</td><td>$list_email</td><td>$list_phone</td><td>$list_status</td></tr>\n";
    }
    else
    {
        print "<tr><td bgcolor=#" . sprintf("%06x", $list_color) . ">$list_name</td><td>$list_email</td><td>$list_phone</td><td>$list_status</td></tr>\n";
    }
}

print "</table>\n";

?>

<p>
The <b>Administrator</b> runs the system and should be contacted for any
technical problems encountered.  Also, the administrator can manually fix
or modify data in the system if needed.
<p>
<b>Power User</b>s can make reservations that override existing reservations.
Users whose resevations are affected by power users will be automatically
notifed via email.
