<?php

if ((include 'header.php') == FALSE)
{
    return;
}

$userid = $_SESSION['userid'];

$settings_changed = FALSE;
$error = FALSE;

print "<input type=\"button\" onClick=\"self.close();\" value=\"Close Window\"><p>\n";

$email = $_REQUEST['email'];
$name = $_REQUEST['name'];
$phone = $_REQUEST['phone'];
$password1 = $_REQUEST['password1'];
$password2 = $_REQUEST['password2'];

if (isset($email) && $email != "")
{
    $newline = "
";

    if (!strstr($email, '@') or !strstr($email, '.') or strstr($email, $newline) or strstr($email, ": "))
    {
        print "<font color=#FF0000><b>Email address not valid</b></font><p>\n";
        $error = TRUE;
    }
    else
    {
        $conn = mysql_connect("localhost", $db_user, $db_pass);
        
        $query = "update Users SET email='$email' WHERE userid=$userid";
        
        $obs_result = mysql_db_query($db_name, $query, $conn);
        
        print "<b>Email address updated</b><p>\n";
        
        $settings_changed = TRUE;
    }
}

if (isset($phone) && $phone != "")
{
    if (strlen($phone) < 10)
    {
        print "<font color=#FF0000><b>Phone number too short</b></font><p>\n";
        $error = TRUE;
    }
    else
    {
        $conn = mysql_connect("localhost", $db_user, $db_pass);
        
        $query = "update Users SET phone='$phone' WHERE userid=$userid";
        
        $obs_result = mysql_db_query($db_name, $query, $conn);
        
        print "<b>Phone number updated</b><p>\n";
        
        $settings_changed = TRUE;
    }
}

if (isset($name) && $name != "")
{
    if (strlen($name) < 3)
    {
        print "<font color=#FF0000><b>Full name too short</b></font><p>\n";
        $error = TRUE;
    }
    else
    {
        $conn = mysql_connect("localhost", $db_user, $db_pass);
        
        $query = "update Users SET name='$name' WHERE userid=$userid";
        
        $obs_result = mysql_db_query($db_name, $query, $conn);
        
        print "<b>Full name updated</b><p>\n";
        
        $settings_changed = TRUE;
    }
}

if (isset($password1) && $password1 != "")
{
    if ($password1 == $password2)
    {
        if (strlen($password1) < 3)
        {
            print "<font color=#FF0000><b>Password too short</b></font><p>\n";
            $error = TRUE;
        }
        else
        {
            $conn = mysql_connect("localhost", $db_user, $db_pass);
            
            $query = "update Users SET password='$password1' WHERE userid=$userid";
            
            $obs_result = mysql_db_query($db_name, $query, $conn);
            
            print "<b>Password updated</b><p>\n";
            
            $settings_changed = TRUE;
        }
    }
    else
    {
        print "<font color=#FF0000><b>Passwords do not match</b></font><p>\n";
    }
}

if ($error || !$settings_changed)
{
    print "<i>Fill in the following fields to change that item.  If left blank, it will not be changed.</i><p>\n";
    print "<form method=\"post\" action=\"account_settings.php\">\n";
    print "<table>\n";
    print "<tr><td></td></tr>\n";
    print "<tr><td>New email address:</td><td><input size=40 name=\"email\" value=\"$email\"></td></tr>\n";
    print "<tr><td>New full name:</td><td><input size=40 name=\"name\" value=\"$name\"></td></tr>\n";
    print "<tr><td>New phone number:</td><td><input size=20 name=\"phone\" value=\"$phone\"></td></tr>\n";
    print "<tr><td>New password:</td><td><input type=password size=20 name=\"password1\" value=\"$password1\"></td></tr>\n";
    print "<tr><td>Retype new password:</td><td><input type=password size=20 name=\"password2\" value=\"$password2\"></td></tr>\n";
    print "</table><p>\n";
    print "<input type=submit value=\"Update Account Settings\">\n";
    print "</form>\n";
}

?>
