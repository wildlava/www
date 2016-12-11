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

if (isset($_REQUEST['start']))
{
    $start = mysql_real_escape_string($_REQUEST['start']);
    $end = mysql_real_escape_string($_REQUEST['end']);
}
else
{
    $start = mysql_real_escape_string($_REQUEST['start_year'] . "-" . $_REQUEST['start_month'] . "-" . $_REQUEST['start_day']);
    $end = mysql_real_escape_string($_REQUEST['end_year'] . "-" . $_REQUEST['end_month'] . "-" . $_REQUEST['end_day']);
}

$start_time = strtotime($start);
$end_time = strtotime($end);

$comments = mysql_real_escape_string($_REQUEST['comments']);

if (isset($_REQUEST['confirmed']))
{
    $confirmed = mysql_real_escape_string($_REQUEST['confirmed']);
}

$error = FALSE;
$need_confirm = FALSE;

if ($start_time > $end_time)
{
    $error = TRUE;
    print "<font color=#FF0000><b>Start time is later than end time</b></font>\n";
}
 else
{
    $res_numbers = array();
    $res_userids = array();
    $res_starts = array();
    $res_ends = array();
    $res_commentss = array();

    $result = mysql_db_query($db_name, "SELECT * FROM Reservations ORDER BY start");
    while ($data = mysql_fetch_array($result, MYSQL_ASSOC))
    {
        if (!is_array($data))
        {
            break;
        }
        
        $res_numbers[] = $data["number"];
        $res_userids[] = $data["userid"];
        $res_starts[] = $data["start"];
        $res_ends[] = $data["end"];
        $res_commentss[] = $data["comments"];
    }
    
    for ($pass=0; $pass<2; ++$pass)
    {
        if ($error or $need_confirm)
        {
            break;
        }
        
        for ($t=0; $t<count($res_numbers); ++$t)
        {
            $res_userid = $res_userids[$t];
            if ($pass == 0 and $res_userid == $userid)
            {
                continue;
            }
            else if ($pass == 1 and $res_userid != $userid)
            {
                continue;
            }
            
            $res_number = $res_numbers[$t];
            $res_start = $res_starts[$t];
            $res_end = $res_ends[$t];
            $res_start_time = strtotime($res_start);
            $res_end_time = strtotime($res_end);
            $res_comments = $res_commentss[$t];
        
            $query = "SELECT * from Users where userid=$res_userid";
            $result = mysql_db_query($db_name, $query);
            while ($data = mysql_fetch_array($result, MYSQL_ASSOC))
            {
                if (!is_array($data))
                {
                    break;
                }
                
                $res_name = $data["name"];
                $res_email = $data["email"];
                $res_color = $data["color"];
            }
            
            $conflict = FALSE;
            $start_exposed = FALSE;
            $end_exposed = FALSE;
        
            // Check for covering old reservation
            if ($start_time <= $res_start_time and $end_time >= $res_end_time)
            {
                $conflict = TRUE;
            }
            else
            {
                // Check for start time inside old reservation
                if ($start_time > $res_start_time and $start_time <= $res_end_time)
                {
                    $conflict = TRUE;
                    $start_exposed = TRUE;
                }
            
                // Check for end time inside old reservation
                if ($end_time >= $res_start_time and $end_time < $res_end_time)
                {
                    $conflict = TRUE;
                    $end_exposed = TRUE;
                }
            }
        
            if ($conflict)
            {
                if ($res_userids[$t] == $userid)
                {
                    $result = mysql_db_query($db_name, "DELETE FROM Reservations WHERE number=$res_number");
                }
                else if (strstr($priv, "a") or strstr($priv, "p"))
                {
                    //$res_name = "none";
                    //$res_color = 0;

                    if ($confirmed)
                    {
                        if (!$start_exposed and !$end_exposed)
                        {
                            $query = "DELETE FROM Reservations WHERE number=$res_number";
                            $result = mysql_db_query($db_name, $query);

                            $to = $res_email;
                            $from_header = "From: reservations@wildlava.com";
                            $message = "This is an automated email to notify you that your beach house reservation\nhas been deleted by $name to accomodate the following new reservation:\n\nStart date: " . sqldate2friendly($start) . "\nEnd date: " . sqldate2friendly($end) . "\n\nPlease check the beach house reservation page.\n";
                            $subject = "ALERT: Your beach house reservation has been deleted";
                            
                            mail($to, $subject, $message, $from_header);
                        }
                        else if ($start_exposed and $end_exposed)
                        {
                            $new_res_end = strftime("%Y-%m-%d", $start_time - (6 * 3600));
                            
                            $query = "UPDATE Reservations SET end='$new_res_end' WHERE number=$res_number";
                            $result = mysql_db_query($db_name, $query);

                            
                            $new_res_start = strftime("%Y-%m-%d", $end_time + (30 * 3600));
                            $query = "INSERT INTO Reservations VALUES (0, $res_userid, '$new_res_start', '$res_end', '" . addslashes($res_comments) . "')";
                            $result = mysql_db_query($db_name, $query);

                            $to = $res_email;
                            $from_header = "From: reservations@wildlava.com";
                            $message = "This is an automated email to notify you that your beach house reservation\nhas been altered by $name to accomodate the following new reservation:\n\nStart date: " . sqldate2friendly($start) . "\nEnd date: " . sqldate2friendly($end) . "\n\nPlease check the beach house reservation page.\n";
                            $subject = "ALERT: Your beach house reservation has been changed";
                            
                            mail($to, $subject, $message, $from_header);
                        }
                        else
                        {
                            if ($start_exposed)
                            {
                                $new_res_end = strftime("%Y-%m-%d", $start_time - (6 * 3600));

                                $query = "UPDATE Reservations SET end='$new_res_end' WHERE number=$res_number";
                                $result = mysql_db_query($db_name, $query);
                            }
                            else
                            {
                                $new_res_start = strftime("%Y-%m-%d", $end_time + (30 * 3600));

                                $query = "UPDATE Reservations SET start='$new_res_start' WHERE number=$res_number";
                                $result = mysql_db_query($db_name, $query);
                            }

                            $to = $res_email;
                            $from_header = "From: reservations@wildlava.com";
                            $message = "This is an automated email to notify you that your beach house reservation\nhas been altered by $name to accomodate the following new reservation:\n\nStart date: " . sqldate2friendly($start) . "\nEnd date: " . sqldate2friendly($end) . "\n\nPlease check the beach house reservation page.\n";
                            $subject = "ALERT: Your beach house reservation has been changed";
                            
                            mail($to, $subject, $message, $from_header);
                        }
                    }
                    else
                    {
                        if (!$start_exposed and !$end_exposed)
                        {
                            print "Reservation from $res_start to $res_end for $res_name will be deleted<p>\n";
                        }
                        else if ($start_exposed and $end_exposed)
                        {
                            print "Reservation from $res_start to $res_end for $res_name will be split<p>\n";
                        }
                        else
                        {
                            print "Reservation from $res_start to $res_end for $res_name will be modified<p>\n";
                        }
                    
                        $need_confirm = TRUE;
                    }
                }
                else
                {
                    $error = TRUE;
                    print "<font color=#FF0000><b>Times conflict with another reservation</b></font>\n";
                    break;
                }
            }
        }
    }
}

if ($error)
{
    print "<p><input type=button value=\"Try Again\" onClick=\"top.location='index.php';\">\n";
}
else if ($need_confirm)
{
    print "<p><b>The above actions will be taken if you proceed</b><p>\n";
    print "<p><input type=button value=\"Cancel\" onClick=\"top.location='index.php';\">\n";
    print "<input type=button value=\"Proceed\" onClick=\"top.location='add_reservation.php?confirmed=1&start=" . $start . "&end=" . $end . "&comments=" . urlencode($comments) . "';\">\n";
}
else
{
    $query = "INSERT INTO Reservations VALUES (0, $userid, '$start', '$end', '" . addslashes($comments) . "')";
    $result = mysql_db_query($db_name, $query);

    $to = $email;
    $from_header = "From: reservations@wildlava.com";
    $message = "This is an automated email to confirm your beach house reservation:\n\nStart date: " . sqldate2friendly($start) . "\nEnd date: " . sqldate2friendly($end) . "\n\nDue to numerous expenses for continued upkeep and improvements, we have had to increase the per night fee to $100.  This will apply only during the vacation dates of the last two weeks in June through the first two weeks of September.  The rest of the year will remain the same as before ($25 per night).\n\nPlease make check payable to John Burke and mail check to:\n\nJohn Burke/Beach House Fund\n80 Boyles Street\nBeverly, MA  01915\n\nDuring the months of July and August in particular, the weeks\nbooked should begin on Sunday AFTER noon, and end on the following\nSunday BEFORE noon.  Over the past years, everyone has shown great respect\nfor the house, and has left it in the same or better condition than they\nfound it.  Thank you all for your help and consideration.  Be sure to\nnotice current information posted at the house.  Enjoy your stay.\n\nIf your plans change, please be sure to make changes to the web site.";
    $subject = "Your new beach house reservation";
    
    mail($to, $subject, $message, $from_header);

    print "<script language=\"JavaScript\">\n";
    print "top.location='index.php';\n";
    print "</script>\n";
}

?>
