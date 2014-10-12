<?php

session_start();
ob_start();

function explore_log($s)
{
    //$fp = fopen("/home/html/explore_logs/log", "a");
    //fprintf($fp, "[%s %s] %s\n", gmdate("Y-M-d H:i:s"), $_SERVER['REMOTE_ADDR'], $s);
    //fclose($fp);
}

?>

<HTML>
<HEAD>
<TITLE>The "Explore" Adventure Games</TITLE>
</HEAD>

<body bgcolor="#aa8822">

<center>
<h1>The "Explore" Adventure Games</h1>

<?php

$SCREEN_LINES = 16;

$new_advname = $_REQUEST['advname'];
$advname = $_SESSION['advname'];
//$enter = $_REQUEST['enter'];
$state = $_SESSION['state'];
$command = $_REQUEST['command'];
$screen_buffer = $_SESSION['screen_buffer'];
$screen_save_lines = $_SESSION['screen_save_lines'];
$output_buffer = array();
$last_prompt = $_SESSION['prompt'];

if (!isset($screen_save_lines))
{
    $screen_save_lines = $SCREEN_LINES;
}

if (!isset($last_prompt) or !$last_prompt)
{
    $last_prompt = '?';
}

if (isset($new_advname))
{
    // Check for bad characters in name, which could be a security issue
    // when the name is passed as part of a command argument (also
    // potentially a problem when making the cookie name).
    if ($new_advname != "")
    {
        $valid = true;
        for ($i=0; $i<strlen($new_advname); ++$i)
        {
            if ($new_advname[$i] < 'a' || $new_advname[$i] > 'z')
            {
                $valid = false;
                break;
            }
        }
    }
    else
    {
        $valid = false;
    }

    if ($valid)
    {
        $advname = $new_advname;
        $_SESSION['advname'] = $advname;
    }
    else
    {
        unset($advname);
        unset($_SESSION['advname']);
    }
}

if (isset($advname))
{
    $cookie_name = "explore_suspended_game_" . $advname;
    //if (isset($_COOKIE[$cookie_name]))
    //{
    //$last_suspend = urldecode($_COOKIE[$cookie_name]);
    $last_suspend = $_COOKIE[$cookie_name];
    //print $last_suspend;
    //}
    
    $suspend_param = "";
    if (isset($last_suspend))
    {
        $suspend_param = " -s " . escapeshellarg(stripslashes($last_suspend));
    }

    if (isset($command))
    {
        $esc_command = escapeshellarg($command);
        if ($esc_command == "")
        {
            $esc_command = "''";
        }
        
        //$fp = popen("ruby explore.rb -c " . $esc_command . " -f $advname.exp -r " . escapeshellarg($state) . $suspend_param, "r");
        //print htmlspecialchars("python explore.py --one-shot -c " . $esc_command . " -f $advname.exp -r " . escapeshellarg($state) . $suspend_param);
        $fp = popen("python explore.py --one-shot -c " . $esc_command . " -f $advname.exp -r " . escapeshellarg($state) . $suspend_param, "r");

        $output_buffer[] = $last_prompt . $command;

        explore_log("In game: " . $advname . " - Issuing command: " . $command);
    }
    else
    {
        // Clear screen
        unset($screen_buffer);

        //$fp = popen("ruby explore.rb --one-shot -f $advname.exp" . $suspend_param, "r");
        $fp = popen("python explore.py --one-shot -f $advname.exp" . $suspend_param, "r");

        explore_log("Starting game: " . $advname);
    }

    $state = false;
    $prompt = false;
    $won = false;
    $dead = false;
    $end = false;
    
    while ($line = fgets($fp))
    {
        while (substr($line, -1) == "\n")
        {
            $line = substr($line, 0, -1);
        }

        if (strlen($line) == 0)
        {
            $output_buffer[] = " ";
        }
        else
        {
            if (substr($line, 0, 1) == "%")
            {
                if (substr($line, 1, 7) == "PROMPT=")
                {
                    $prompt = substr($line, 8);
                }
                else if (substr($line, 1, 6) == "STATE=")
                {
                    $state = substr($line, 7);
                }
                else if (substr($line, 1, 3) == "WIN")
                {
                    $won = true;
                }
                else if (substr($line, 1, 3) == "DIE")
                {
                    $dead = true;
                }
                else if (substr($line, 1, 3) == "END")
                {
                    $end = true;
                }
                else if (substr($line, 1, 7) == "SUSPEND" && $state)
                {
                    setcookie("explore_suspended_game_$advname", $state, time() + 60*60*24*30);
#setcookie("explore_suspended_game_$advname", $state, 0);
                }
            }
            else
            {
                $output_buffer[] = $line;
            }
        }
    }
    
    pclose($fp);

    $_SESSION['state'] = $state;
    $_SESSION['prompt'] = $prompt;
    if ($prompt)
    {
        $output_buffer[] = $prompt;
    }
}
else
{
    unset($screen_buffer);
    
    $output_buffer[] = "Please select an adventure.";
    $output_buffer[] = " ";
    $output_buffer[] = " ";
    $output_buffer[] = " ";
    $output_buffer[] = " ";
    $output_buffer[] = " ";
}

$num_output_lines = count($output_buffer);

if (!isset($screen_buffer))
{
    $screen_buffer = array();
    for ($i=0; $i<$SCREEN_LINES; ++$i)
    {
        $screen_buffer[] = " ";
    }
}
    
// Move lines up on screen
$start_line = $num_output_lines - ($SCREEN_LINES - $screen_save_lines);
for ($i=$start_line; $i<$screen_save_lines; ++$i)
{
    $screen_buffer[$i - $start_line] = $screen_buffer[$i];
}

// Add new output lines to screen
for ($i=0; $i<$num_output_lines; ++$i)
{
    $screen_buffer[$i + $SCREEN_LINES - $num_output_lines] = $output_buffer[$i];
}

// Display screen
print "<table width=70% cellpadding=5><tr><td colspan=2 bgcolor=\"#303030\" NOWRAP><pre><font color=lightgreen>\n";

for ($i=0; $i<$SCREEN_LINES; ++$i)
{
    //if ($i == ($SCREEN_LINES - 1) && substr($screen_buffer[$i], -1) == "\n" )
    //{
    //    print substr($screen_buffer[$i], 0, -1);
    //}
    //else
    //{
    print $screen_buffer[$i];
    //if ($i != ($SCREEN_LINES - 1))
    //{
    print "\n";
    //}
    //}

    //print "<br>\n";
}

print "</font></pre></td></tr><tr><td colspan=2 bgcolor=\"#00aacc\">\n";

if (!isset($advname))
{
    print "Please select an adventure\n";
}
else if ($won)
{
    print "Congratulations, you have successfully completed this adventure!\n";
    explore_log("Won game: " . $advname);
    unset($advname);
    unset($_SESSION['advname']);
}
else if ($dead)
{
    print "Game over.\n";
    explore_log("Died in game: " . $advname);
    unset($advname);
    unset($_SESSION['advname']);
}
else if ($end)
{
    //print "Game over.\n";
    explore_log("Quit game: " . $advname);
    unset($advname);
    unset($_SESSION['advname']);
}
else
{
    // Present command form to user
    print "<form id=\"command_form\" name=\"command_form\" method=post action=\"explore.php\">\n";
    print "<input id=command_field size=40 name=\"command\" value=\"\">\n";
    //print "<input type=hidden name=\"state\" value=\"$state\">\n";
    print "<input type=submit name=\"enter\" value=\"Enter\">\n";
    print "</form>\n";
    
    // Put focus in command field
    print "<script type=\"text/javascript\">\n";
    print "document.command_form.command_field.focus();\n";
    print "</script>\n";
}

print "</td></tr><tr><td bgcolor=\"#00aacc\">\n";
print "To start a new game, click one of the following:<p>\n";
print "<a href=\"/explore/explore.php?advname=cave\">cave</a><br>\n";
print "<a href=\"/explore/explore.php?advname=mine\">mine</a><br>\n";
print "<a href=\"/explore/explore.php?advname=castle\">castle</a><br>\n";
print "<a href=\"/explore/explore.php?advname=haunt\">haunt</a><br>\n";
print "<a href=\"/explore/explore.php?advname=porkys\">porkys</a>\n";

print "</td><td bgcolor=\"#00aacc\">\n";
if ($suspend_param != "")
{
    print "<b><font color=\"#aa4411\">You have a suspended game.</font></b><br>To resume, type \"resume\".<p>\n";
}
print "To save a game, type \"suspend\".<p>\n";
print "<font size=-1>Typing \"help\" will list some frequently used commands, but remeber that there are many other possible commands to try (things like \"get lamp\" or \"eat taco\").  If you are having trouble, try stating it differently or using fewer words.</font>\n";

print "</td></tr></table>\n";

$_SESSION['screen_buffer'] = $screen_buffer;

if ($prompt)
{
    $_SESSION['screen_save_lines'] = $SCREEN_LINES - 1;
}
else{
    $_SESSION['screen_save_lines'] = $SCREEN_LINES;
}

print "</center>\n";

ob_end_flush();

if (!isset($advname))
{
?>

<p>

<img align=left src="explore_launch_icon.png">
<h3>Now available as an <a href="http://www.android.com/">Android</a> phone app!  If you have an Android phone, look in Android Market for "Explore".</h3>
<br clear=left>
        
<p>

When I was 15 or so, my cousin, De, and I were into playing adventure games,
like the mother of all text adventure games,
"<a href="http://www.rickadams.org/adventure/">Adventure</a>".
We wanted to make our own, so we wrote a simple one, but it was hard-coded
and was a pain to create.  So we came up with the idea to make a program
that could interpret adventure "game files" that were written in a kind
of adventure "language".  So we both wrote programs in
<a href="explore.bas">BASIC</a> to do this
on TRS-80 computers (wow, 1.77 MHz!),
and we wrote adventures in separate text files.  We later merged our work
into this program, which was dubbed "Explore".
By the way, I was really bummed when a guy named
<a href="http://www.msadams.com/index.htm">Scott Adams</a>
(not the Dilbert dude!) came out with a commercial program that
used the same concept!  Just think of all the money <i>we</i> could have made!
<p>
We came up with three adventures that were written
in the wee hours of the morning on three separate occasions listening
to Steely Dan.  It was kind of a mystical inspiration I would say.
<p>
De is no longer with us, but these games live on for me as a great memory of our friendship, and I hope that they allow a little piece of him to endure.
<p>
Years later I dug up the old BASIC program and rewrote it in
C (note that the C version and the
BASIC version are no longer being maintained, so future adventure game files
or newer revisions of the old ones won't work with the old code).
<p>
A few years after this I rewrote the whole system in Java
as a way to learn the language.  And years after that, I rewrote the
whole thing in Python and later in Ruby.  The Python version is used here.

<!--
Now, as a way to explore the new languange called
"Ruby", I translated the Python code to Ruby.  Both Python and Ruby versions
are now maintained, and either may be used here.
-->

Now you too can play these historic games on-line!
<p>
When starting a
game, you have to pick an adventure.  Your choices are:

<ul>

<li><b>Cave</b> - "Enchanted Cave" was the first of our adventure games.
The fact that it takes place in a cave, like the original Adventure, was no
coincidence.  This adventure had lots of rooms, but the capabilities of the
Explore Adventure Language were just being developed, so even though I think
this one came out pretty well, it's not as rich in features as the later ones.

<li><b>Mine</b> - "Lost Mine" takes place in an old coal mine
in a desert environment,
complete with scary skeletons, mining cars, and lots of magic.  We started to
get a little more descriptive in this one, and we also added features to
the adventure language to make things seem a little "smarter."

<li><b>Castle</b> - "Medieval Castle" was the final in the "trilogy"
of our late-nite
teenage adventure creativity.  This one forced us to add even more features to
the language, and I believe it really became "sophisticated" with this one.
Castle is perhaps the most colorful of the adventures, but not as mystical
somehow as Enchanted Cave.  De and I didn't make any more games after this one.

<li><b>Haunt</b> - "Haunted House" was not an original creation.  It is a clone
of Radio Shack's
<a href="http://www.simology.com/smccoy/trs80/model134/mhauntedhouse.html">
Haunted House</a> adventure game that I re-created in the Explore Adventure
Language as a test of the language's power.  I had to play the original quite
a bit to get it right, since I was going on the behavior of the game and not
its code.

<li><b>Porkys</b> - "Porky's" is the only one in which I had no involvement.
A friend
in Oklahoma at the time took the Explore language and created this one,
inspired
by the movie of the same name.  It was especially cool to play and solve
an adventure written by someone else with my own adventure language!
Warning, this one has "ADULT CONTENT AND LANGUAGE!"
</ul>

<hr>

Other text adventure related links:
<ul>
<li> <a href="http://www.rickadams.org/adventure/">The Colossal Cave Adventure Page</a>
<li> <a href="http://www.plugh.com/">A hollow voice says "Plugh".</a>
<li> <a href="http://www.msadams.com/index.htm">Scott Adams' Adventure game writer home page</a>
</ul>

<?php
}
?>

<p>
<table width=100%>
<tr>
<td align=right><i><a href="http://www.wildlava.com/">wildlava.com</a></i></td>
</tr>
</table>

</body>
</html>
