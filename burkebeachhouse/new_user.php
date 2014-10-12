<?php

$no_login = TRUE;

if ((include 'header.php') == FALSE)
{
    return;
}

//print "<input type=button onClick=\"self.close();\" value=\"Dismiss\"><p>\n";

$account_created = FALSE;
    
if (isset($_REQUEST['email']))
{
    $conn = mysql_connect("localhost", $db_user, $db_pass);
            
    $email = mysql_real_escape_string($_REQUEST['email']);
    $password1 = mysql_real_escape_string($_REQUEST['password1']);
    $password2 = mysql_real_escape_string($_REQUEST['password2']);
    $name = mysql_real_escape_string($_REQUEST['name']);
    $phone = mysql_real_escape_string($_REQUEST['phone']);
    
    $color = 0;
/*
    $red = 0;
    $green = 0;
    $blue = 0;
    $color_ok = FALSE;
    while (!$color_ok)
    {
        $red = rand(8, 15) << 4;
        $green = rand(8, 15) << 4;
        $blue = rand(8, 15) << 4;
        if ($red == $green and $green == $blue)
        {
            break;
        }

        $color = ($red << 16) + ($green << 8) + $blue;
        
        $query = "SELECT color FROM Users";
        $result = mysql_db_query($db_name, $query, $conn);
        while ($data = mysql_fetch_array($result, MYSQL_ASSOC))
        {
            if (!is_array($data))
            {
                break;
            }
            
            $list_color = $data['color'];

            if ($list_color != $color)
            {
                $color_ok = TRUE;
            }
        }
    }
*/
    
    $newline = "
";
    
    if ($password1 == $password2)
    {
        if (strlen($password1) < 3)
        {
            print "<font color=#FF0000><b>Password too short</b></font><p>\n";
        }
        else if (!strstr($email, '@') or !strstr($email, '.') or strstr($email, $newline) or strstr($email, ": "))
        {
            print "<font color=#FF0000><b>Email address not valid</b></font><p>\n";
        }
        else if (strlen($name) < 3)
        {
            print "<font color=#FF0000><b>Full name too short</b></font><p>\n";
        }
        else if (strlen($phone) < 10)
        {
            print "<font color=#FF0000><b>Phone number too short</b></font><p>\n";
        }
        else
        {
            $priv = "r";
            
            $query = "SELECT userid FROM Users WHERE email='$email'";
            $obs_result = mysql_db_query($db_name, $query, $conn);
            if (mysql_fetch_array($obs_result, MYSQL_ASSOC))
            {
                print "<font color=#FF0000><b>Email address already registered</b></font><p>\n";
            }
            else
            {
                $query = "INSERT INTO Users VALUES (0, '$email', '$password1', '$priv', '$name', '$phone', $color)";
                $obs_result = mysql_db_query($db_name, $query, $conn);
                if (!$obs_result)
                {
                    print "<font color=#FF0000><b>Email address already registered</b></font><p>\n";
                }
                else
                {
                    print "<b>New account created</b><p>\n";
                    $account_created = TRUE;
                    
                    $to = "joanne@skyrush.com, joe@wildlava.com";
                    $from_header = "From: reservations@wildlava.com";
                    $message = "This is an automated email to notify you that a new user has registered:\n\nName: $name\nEmail: $email\nPhone: $phone\n";
                    $subject = "New beach house user";
                    
                    mail($to, $subject, $message, $from_header);
                    
                    print "<input type=button value=\"Log in\" onClick=\"top.location='index.php';\">";
                }
            }
        }
    }
    else
    {
        print "<font color=#FF0000><b>Passwords do not match</b></font><p>\n";
    }
}
else
{
    $email = "";
    $password1 = "";
    $password2 = "";
    $name = "";
    $phone = "";
}

if (!$account_created)
{
    print "<form method=\"post\" action=\"new_user.php\">\n";
    print "<table>\n";
    print "<tr><td>Email address:</td><td><input size=30 name=\"email\" value=\"$email\"></td></tr>\n";
    print "<tr><td>Password:</td><td><input type=password size=20 name=\"password1\" value=\"$password1\"></td></tr>\n";
    print "<tr><td>Retype password:</td><td><input type=password size=20 name=\"password2\" value=\"$password2\"></td></tr>\n";
    print "<tr><td>Full name:</td><td><input size=30 name=\"name\" value=\"$name\"></td></tr>\n";
    print "<tr><td>Phone number:</td><td><input size=30 name=\"phone\" value=\"$phone\"></td></tr>\n";
    print "</table><p>\n";
    print "<input type=submit value=\"Create new account\">\n";
    print "</form>\n";
}

?>
