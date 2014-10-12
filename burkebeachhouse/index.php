<?php

if ((include 'header.php') == FALSE)
{
    return;
}

if ((include 'date_funcs.php') == FALSE)
{
    return;
}

$month_names = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
$month_days = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

$userid = $_SESSION['userid'];
$priv = $_SESSION['priv'];

$conn = mysql_connect("localhost", $db_user, $db_pass);

$user_colors = array();
$result = mysql_db_query($db_name, "SELECT userid,name,color FROM Users");
while ($data = mysql_fetch_array($result, MYSQL_ASSOC))
{
    if (!is_array($data))
    {
        break;
    }
    
    $user_names[$data["userid"]] = $data["name"];
    $user_colors[$data["userid"]] = $data["color"];
}

$res_userids = array();
$res_colors = array();
$res_numbers = array();
$res_names = array();
$res_starts = array();
$res_ends = array();
$res_start_times = array();
$res_end_times = array();
$res_comments = array();
$res_emails = array();

$year = 2013;

$result = mysql_db_query($db_name, "SELECT * FROM Reservations WHERE EXTRACT(YEAR from start)=$year ORDER BY start");
while ($data = mysql_fetch_array($result, MYSQL_ASSOC))
{
    if (!is_array($data))
    {
        break;
    }
    
    $res_numbers[] = $data["number"];
    $res_userid = $data["userid"];
    $res_userids[] = $res_userid;
    $res_start = $data["start"];
    $res_starts[] = $res_start;
    $res_end = $data["end"];
    $res_ends[] = $res_end;
    $res_start_times[] = strtotime($res_start);
    $res_end_times[] = strtotime($res_end);
    $res_names[] = $user_names[$res_userid];
    $res_colors[] = $user_colors[$res_userid];
    $res_comments[] = $data["comments"];

    $query = "SELECT * from Users where userid=$res_userid";
    $user_result = mysql_db_query($db_name, $query);
    while ($user_data = mysql_fetch_array($user_result, MYSQL_ASSOC))
    {
        if (!is_array($user_data))
        {
            break;
        }
        
        $res_emails[] = $user_data["email"];
    }
}

print "<h2><center>" . $year . "</center></h2>\n";
print "<center><table border bordercolor=\"#dddddd\" bgcolor=\"#dddddd\" cellpadding=1 cellspacing=1>\n";

$year_start_day = 6;
for ($i=2000; $i<$year; ++$i)
{
    if (($i % 400) == 0 or (!(($i % 100) == 0) and ($i % 4) == 0))
    {
        $year_start_day = ($year_start_day + 366) % 7;
    }
    else
    {
        $year_start_day = ($year_start_day + 365) % 7;
    }
}

$doy = 1;
for ($j=0; $j<3; ++$j)
{
    print "<tr>\n";
    for ($i=0; $i<4; ++$i)
    {
        $month = $j * 4 + $i + 1;

        $start_day = ($doy + $year_start_day - 1) % 7;
        $num_days = $month_days[$month - 1];

        // Calculate leap year
        if ($month == 2 and (($year % 400) == 0 or (!(($year % 100) == 0) and ($year % 4) == 0)))
        {
            $num_days += 1;
        }
        
        print "<td>";
        print "<center>" . $month_names[$month - 1] . "</center>";
        print "<table cellpadding=4>\n";
        for ($w=0; $w<6; ++$w)
        {
            print "<tr>\n";
            for ($d=0; $d<7; ++$d)
            {
                $grid_num = $w * 7 + $d;
                $date = $grid_num - $start_day + 1;

                if ($date >= 1 and $date <= $num_days)
                {
                    $time = mktime(0, 0, 0, $month, $date, $year);
                    $date_reserved = FALSE;
                    for ($t=0; $t<count($res_start_times); ++$t)
                    {
                        if ($time >= $res_start_times[$t] and $time <= $res_end_times[$t])
                        {
                            $date_reserved = TRUE;
                            print "<td align=right bgcolor=#" . sprintf("%06x", $res_colors[$t]) . ">";
                            break;
                        }
                    }
                    
                    if (!$date_reserved)
                    {
                        print "<td align=right>";
                    }

                    print $date . "</td>\n";

                    $doy += 1;
                }
                else
                {
                    print "<td></td>\n";
                }
            }

            print "</tr>\n";
        }
        
        print "</table></td>\n";
    }

    print "</tr>\n";
}

print "</table></center>\n";

if (count($res_numbers) > 0)
{
    print "<hr><h3>Current reservations</h3><p>\n";
    
    print "<table border bordercolor=\"#dddddd\" bgcolor=\"#dddddd\" cellpadding=2 cellspacing=1>\n";
    print "<tr><th></th><th>Name</th><th>Start Date</th><th>End Date</th><th>Comments</th></tr>\n";
    
    for ($i=0; $i<count($res_numbers); ++$i)
    {
        print "<tr>";
        if ($res_userids[$i] == $userid or strstr($priv, "a") or strstr($priv, "p"))
        {
            print "<td><table cellpadding=0 cellspacing=0><td><input type=\"button\" style=\"background-color: #ff8899\" value=\"Delete\" onClick=\"delete_reservation('" . $res_numbers[$i] . "', '" . $res_emails[$i] . "', '" . $res_starts[$i] . "', '" . $res_ends[$i] . "');\"></td></table></td>\n";
        }
        else
        {
            print "<td></td>\n";
        }
        
        print "<td bgcolor=#" . sprintf("%06x", $res_colors[$i]) . " nowrap>" . $res_names[$i] . "</td><td nowrap><pre>" . sqldate2friendly($res_starts[$i]) . "</pre></td><td nowrap><pre>" . sqldate2friendly($res_ends[$i]) . "</pre></td><td>" . $res_comments[$i] . "</td></tr>\n";
    }

    print "</table>\n";
}
else
{
    print "<hr><h3>There are no reservations in the system.</h3><p>\n";
}

if (strstr($priv, "r"))
{
    return;
}

print "<hr><h3>Add or change a reservation</h3><p>\n";
print "<form method=\"post\" action=\"add_reservation.php\">\n";

print "<table><tr><td>Start:</td><td><select size=1 name=\"start_month\">\n";
print "<option value=\"01\">January</option>\n";
print "<option value=\"02\">Febuary</option>\n";
print "<option value=\"03\">March</option>\n";
print "<option value=\"04\">April</option>\n";
print "<option value=\"05\">May</option>\n";
print "<option value=\"06\">June</option>\n";
print "<option value=\"07\">July</option>\n";
print "<option value=\"08\">August</option>\n";
print "<option value=\"09\">September</option>\n";
print "<option value=\"10\">October</option>\n";
print "<option value=\"11\">November</option>\n";
print "<option value=\"12\">December</option>\n";
print "</select>\n";

print "<select size=1 name=\"start_day\">\n";
for ($i=1; $i<=31; ++$i)
{
    if ($i < 10)
    {
        print "<option value=\"0$i\">$i</option>\n";
    }
    else
    {
        print "<option value=\"$i\">$i</option>\n";
    }
}
print "</select></td></tr>\n";

//print "<select size=1 name=\"start_year\">\n";
//for ($i=$year; $i<=$year+4; ++$i)
//{
//    print "<option value=\"$i\">$i</option>\n";
//}
//print "</select>\n";
print "<input type=hidden name=\"start_year\" value=$year>\n";

print "<tr><td>End:</td><td><select size=1 name=\"end_month\">\n";
print "<option value=\"01\">January</option>\n";
print "<option value=\"02\">Febuary</option>\n";
print "<option value=\"03\">March</option>\n";
print "<option value=\"04\">April</option>\n";
print "<option value=\"05\">May</option>\n";
print "<option value=\"06\">June</option>\n";
print "<option value=\"07\">July</option>\n";
print "<option value=\"08\">August</option>\n";
print "<option value=\"09\">September</option>\n";
print "<option value=\"10\">October</option>\n";
print "<option value=\"11\">November</option>\n";
print "<option value=\"12\">December</option>\n";
print "</select>\n";

print "<select size=1 name=\"end_day\">\n";
for ($i=1; $i<=31; ++$i)
{
    if ($i < 10)
    {
        print "<option value=\"0$i\">$i</option>\n";
    }
    else
    {
        print "<option value=\"$i\">$i</option>\n";
    }
}
print "</select></td></tr>\n";

//print "<select size=1 name=\"end_year\">\n";
//for ($i=$year; $i<=$year+4; ++$i)
//{
//    print "<option value=\"$i\">$i</option>\n";
//}
//print "</select><br>\n";
print "<input type=hidden name=\"end_year\" value=$year>\n";

print "<tr><td>Comments:</td><td><input name=\"comments\" value=\"\" size=64></td>\n";

print "</tr></table><p>\n";

?>

<b><font size=+1>
Due to numerous expenses for continued upkeep and improvements, we have had to increase the per night fee to $100.  This will apply only during the vacation dates of the last two weeks in June through the first two weeks of September.  The rest of the year will remain the same as before ($25 per night).</font></b>  <i>-John Burke</i>
<p>
<b>
Please make check payable to John Burke and mail check to:
<p>
John Burke/Beach House Fund
<br>
80 Boyles Street
<br>
Beverly, MA  01915
</b>
<p>

<?php

print "<input type=submit name=\"add_reservation\" value=\"Add or Change Reservation\"></input>";

print "</form>\n";

?>
