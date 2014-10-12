<?php
if ((include 'gbh_db_user.php') == FALSE)
{
    return;
}

session_start();
?>

<html>
<head>
<title>Burke Beach House Scheduler</title>

<script language="JavaScript">

      function delete_reservation(id, email, start, end)
{
    if (confirm("Are you sure you want to delete this reservation?"))
    {
        window.location.href = "delete_reservation.php?res_num=" + id + "&res_email=" + email + "&res_start=" + start + "&res_end=" + end;
    }
}

</script>

</head>

<BODY background="background.jpg" text="#000000">

<TABLE border bordercolor="#cccccc" bgcolor="#cccccc">
<TR>
<TD bgcolor="#000000"><img src="house_small.jpg"></TD>
<TD width="100%" bgcolor="#000000"><center><b><font face="helvetica" size=+3 color="#00dddd">Burke Beach House Scheduler</font></b></center>

<?php

if (isset($_REQUEST['logout']))
{
    unset($_SESSION['userid']);
}

if (!isset($no_login))
{
    if (!auth())
    {

?>

</TD>
</TR>
</table>

<?php

    login_form(isset($_REQUEST['email']));

 return FALSE; 
}
else
{
        $logged_in_user = $_SESSION['name'];
        print "<font face=\"helvetica\"color=\"white\" size=\"-1\"><b><center>You are logged in as $logged_in_user</center></b></font>";

?>

</TD>
</TR>
</table>

<table width="100%">
<tr><td align=right>
<form method="post">
<input type="button" value="User List" onClick="self.open('user_list.php')">
<input type="button" value="Account Settings" onClick="self.open('account_settings.php')">
<input type="button" value="Logout" onClick="window.location.href='index.php?logout=yes';">
</form>
</td></tr>
</table>

<?php
    }
}
 else
 {
?>

</TD>
</TR>
</table>

<?php
 }
?>

<br clear=right>

<?php

function auth()
{
    global $db_name, $db_user, $db_pass;

    //session_start();
    //global $HTTP_SESSION_VARS;
    //global $login, $priv;

//    if (isset($HTTP_SESSION_VARS['login']))
    if (isset($_SESSION['userid']))
    {
        return TRUE;
    }
    elseif (isset($_REQUEST['email']))
    {
        $conn = mysql_connect("localhost", $db_user, $db_pass);

        $login_try = mysql_real_escape_string($_REQUEST['email']);
        $passwd_try = mysql_real_escape_string(strtolower($_REQUEST['password']));

        $result = mysql_db_query($db_name, "SELECT * FROM Users WHERE email='$login_try'", $conn);

        if ($data = mysql_fetch_array($result, MYSQL_ASSOC))
        {
            if (is_array($data))
            {
                $passwd_db = strtolower($data["password"]);

                if ($passwd_db == $passwd_try)
                {
                    $_SESSION['userid'] = $data["userid"];
                    $_SESSION['email'] = $data["email"];
                    $_SESSION['name'] = $data["name"];
                    $_SESSION['priv'] = $data["priv"];
                    //$_SESSION['password'] = $passwd_db;

                    return TRUE;
                }
            }
        }
    }

    unset($_SESSION['userid']);
    //unset($_SESSION['email']);
    //unset($_SESSION['password']);
    //unset($_SESSION['priv']);

    return FALSE;
}

function login_form($error = FALSE)
{
    ?>
    <form method="post" action="index.php">

    <h3>Please log in, or add yourself as a</h3>
         <input type="button" value="New User" onClick="top.location='new_user.php';">
    <h3>Please log in</h3>

    <p>

         <?php if ($error)
         {
             ?>
             <font color=#ff0000><b>The login information you provided was invalid</b></font>
             <?php
         }
        ?>

    <table>

    <tr>
    <td>Email address:</td><td><input name="email" size=16></td>
    </tr>
    <tr>
    <td>Password:</td><td><input type=password name="password" size=16></td>
    </tr>

    </table>

    <input type="submit" value="Login">

    </form>

<p>

<h3>Please visit our <a href="/forum/">forum</a>!  Note: you need to register separately for an account on the forum page (account is not shared with the reservation site) - just pick a any username you like (not your email address).</h3>

<p>

<i>
Thanks to all who have registered as a Burke Beach House user.  The
users permitted to make reservations extend to the direct children and
grandchildren of Dr. Burke, and not to their spouses and children, to
avoid confusion and/or overlapping of reservations in the system.  If
anyone else would like to spend time at the beach house, please contact
a registered user to make the reservation, and put in the comments area
who the reservation is for.  Example, John Burke made reservations for
cousin Marilyn, and put this in the comments section.
<p>
However, any family member is welcome to register for an account to
view the calender!
</i>
<p>
<b><font size=+1>
Due to numerous expenses for continued upkeep and improvements, we have had to increase the per night fee to $100.  This will apply only during the vacation dates of the last two weeks in June through the first two weeks of September.  The rest of the year will remain the same as before ($25 per night).</font></b>  <i>-John Burke</i>
<p>
<b>Instructions regarding this will be given after login.</b>
<?php
}

return TRUE;

?>
