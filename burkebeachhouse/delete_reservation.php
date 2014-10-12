<?php

if ((include 'header.php') == FALSE)
{
    return;
}

if ((include 'date_funcs.php') == FALSE)
{
    return;
}

$userid = $_SESSION['userid'];
$priv = $_SESSION['priv'];
$name = $_SESSION['name'];
$email = $_SESSION['email'];

if (strstr($priv, "r"))
{
    print "<font color=#FF0000><b>Access denied</b></font>\n";
    return;
}

$conn = mysql_connect("localhost", $db_user, $db_pass);

$res_num = mysql_real_escape_string($_REQUEST['res_num']);
$res_email = mysql_real_escape_string($_REQUEST['res_email']);
$res_start = mysql_real_escape_string($_REQUEST['res_start']);
$res_end = mysql_real_escape_string($_REQUEST['res_end']);

print $res_num;
$query = "delete from Reservations where number=$res_num";
//print "$query<br>\n";
$result = mysql_db_query($db_name, $query);

$to = $res_email;
$from_header = "From: reservations@wildlava.com";
if ($email == $res_email)
{
    $message = "This is an automated email to notify you that your beach house reservation\nhas been deleted:\n\nStart date: " . sqldate2friendly($res_start) . "\nEnd date: " . sqldate2friendly($res_end) . "\n";
    $subject = "Your beach house reservation has been deleted";
}
else
{
    $message = "This is an automated email to notify you that your beach house reservation\nhas been deleted by $name:\n\nStart date: " . sqldate2friendly($res_start) . "\nEnd date: " . sqldate2friendly($res_end) . "\n\nPlease check the beach house reservation page.\n";
    $subject = "ALERT: Your beach house reservation has been deleted";
}

mail($to, $subject, $message, $from_header);

?>

<script language="JavaScript">
top.location='index.php';
</script>
