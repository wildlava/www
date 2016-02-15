from mod_python import apache, util, Session, Cookie

import sys
import os
import time

sys.path.append("/home/html/explore_files/")
from explore_lib import play_once

def explore_log(s, ip):
    fp = file('/home/html/explore_logs/log', 'a')
    fp.write('[%s %s] %s\n' % (time.strftime('%Y-%m-%d %H:%M:%S %Z'), ip, s))
    fp.close()
    
SCREEN_LINES = 16

def handler(req):
    session = Session.Session(req)

    # Initialize the page body
    body = []
    
    try:
        advname = session['advname']
    except:
        advname = None
        
    try:
        state = session['state']
    except:
        state = None

    try:
        last_prompt = session['prompt']
    except:
        last_prompt = None

    try:
        screen_buffer = session['screen_buffer']
    except:
        screen_buffer = None
        
    if screen_buffer:
        screen_buffer = screen_buffer.split('\n')
        
    if not hasattr(req, 'form'):
        req.form = util.FieldStorage(req)
        
    new_advname = req.form.getfirst('advname')
    if req.form.has_key('enter'):
        command = req.form.getfirst('command')
        if command == None:
            command = ''
    else:
        command = None

    output_buffer = []
    has_suspended_game = False
    
    if new_advname:
        # Check for bad characters in name, which could be a security issue
        # when the name is passed as part of a command argument (also
        # potentially a problem when making the cookie name).
        if new_advname.isalnum():
            advname = new_advname
            session["advname"] = advname
        else:
            advname = None
            session["advname"] = None
            
    if advname:
        cookies = Cookie.get_cookies(req)
        try:
            cookie = cookies['explore_suspended_game_%s' % (advname)]
            suspend = cookie.value
            #suspend_param = " -s '" + suspend.replace("'", r"\'") + "'"
            has_suspended_game = True
        except:
            suspend = None
            suspend_param = ""
            
        #req.write("Command = " + repr(command) + "\n")
        if command != None:
            #fp = os.popen("python /home/html/explore_files/explore.py -c '" + command.replace("'", r"\'") + "' -f /home/html/explore_files/" + advname + ".exp -r '" + state.replace("'", r"\'") + "'" + suspend_param)
            output = play_once('/home/html/explore_files/' + advname + '.exp', command, state, suspend)
            
            if last_prompt:
                output_buffer.append(last_prompt + command)
            else:
                output_buffer.append("?" + command)
                
            explore_log("In game: " + advname + " - Issuing command: " + command, req.connection.remote_ip)
        else:
            # Clear screen
            screen_buffer = None
            
            #fp = os.popen("python /home/html/explore_files/explore.py --one-shot -f /home/html/explore_files/" + advname + ".exp" + suspend_param)
            output = play_once('/home/html/explore_files/' + advname + '.exp', None, None, suspend)
            
            explore_log("Starting game: " + advname, req.connection.remote_ip)
  
        state = None
        prompt = None
        won = False
        dead = False
        quit = False
        
        #for line in fp:
        for line in output:
            #line = line.strip()
            
            if len(line) == 0:
                output_buffer.append(" ")
            else:
                if line[0] == "%":
                    if line[1:8] == "PROMPT=":
                        prompt = line[8:]
                    elif line[1:7] == "STATE=":
                        state = line[7:]
                    elif line[1:4] == "WIN":
                        won = True
                    elif line[1:4] == "DIE":
                        dead = True
                    elif line[1:4] == "END":
                        quit = True
                    elif line[1:8] == "SUSPEND" and state:
                        new_cookie = Cookie.Cookie("explore_suspended_game_" + advname, state)
                        new_cookie.expires = time.time() + 60*60*24*30
                        Cookie.add_cookie(req, new_cookie)
                else:
                    output_buffer.append(line)
                    
        #fp.close()
        
        session["prompt"] = prompt
        session["state"] = state
        if prompt:
            output_buffer.append(prompt)
    else:
        screen_buffer = None
        
        output_buffer.append("No adventure selected.")
        output_buffer.append(" ")
        output_buffer.append(" ")
        output_buffer.append(" ")
        output_buffer.append(" ")
        output_buffer.append(" ")

        session["state"] = None
        session["prompt"] = None

    # Ready screen for new output
    num_output_lines = len(output_buffer)
    if not screen_buffer:
        # Clear screen
        screen_buffer = (SCREEN_LINES - num_output_lines) * [" "]
    else:
        # Move lines up on screen
        if last_prompt:
            screen_buffer[0:num_output_lines - 1] = []
            screen_buffer[-1:] = []
        else:
            screen_buffer[0:num_output_lines] = []

    # Add new output lines to screen
    screen_buffer.extend(output_buffer)
    #for l in screen_buffer:
    #    req.write("screen_line: " + repr(l) + "\n")
    session['screen_buffer'] = '\n'.join(screen_buffer)
    
    body.append("<center>")
    body.append('<h1>The "Explore" Adventure Series</h1>')
    
    # Display screen
    body.append('<table width=70% cellpadding=5><tr><td colspan=2 bgcolor="#303030" NOWRAP><pre><font color=lightgreen>')
    
    for line in screen_buffer:
        body.append(line)
    
    body.append('</font></pre></td></tr><tr><td colspan=2 bgcolor="#00aacc">')
    
    if not advname:
        body.append("Please select a game from the list below...")
    elif won:
        body.append("Congratulations!  You solved the adventure!")
        explore_log("Won game: " + advname, req.connection.remote_ip)
    elif dead:
        body.append("Game over.")
        explore_log("Died in game: " + advname, req.connection.remote_ip)
    elif quit:
        body.append("Game over.")
        explore_log("Quit game: " + advname, req.connection.remote_ip)
    else:
        # Present command form to user
        body.append('<form id="command_form" name="command_form" method=post action="explore.py">')
        body.append('<input id=command_field size=40 name="command" value="">')
        body.append('<input type=submit name="enter" value="Enter">')
        body.append("</form>")
        
        # Put focus in command field
        body.append('<script type="text/javascript">')
        body.append("document.command_form.command_field.focus();")
        body.append("</script>")

    body.append('</td></tr><tr><td bgcolor="#00aacc">')
    body.append("To start a new game, click one of the following:<p>")
    body.append('<a href="/explore/explore.py?advname=cave">cave</a><br>')
    body.append('<a href="/explore/explore.py?advname=mine">mine</a><br>')
    body.append('<a href="/explore/explore.py?advname=castle">castle</a><br>')
    body.append('<a href="/explore/explore.py?advname=haunt">haunt</a><br>')
    body.append('<a href="/explore/explore.py?advname=porkys">porkys</a>')
    
    body.append('</td><td bgcolor="#00aacc">')

    if has_suspended_game:
        body.append('<b><font color="#aa4411">You have a suspended game.</font></b><br>To resume, type "resume".<p>')
    
    body.append('To save a game, type "suspend".<p>')
    body.append('<font size=-1>Typing "help" will list some frequently used commands, but remeber that there are many other possible commands to try (things like "get lamp" or "eat taco").  If you are having trouble, try stating it differently or using fewer words.</font>')

    body.append("</td></tr></table>")
    body.append("</center>")

    if not advname:
        body.append('<hr>')
        body.append('')
        body.append('When I was 15 or so, my cousin, De, and I were into playing adventure games,')
        body.append('like the mother of all text adventure games,')
        body.append('"<a href="http://www.rickadams.org/adventure/">Adventure</a>".')
        body.append('We wanted to make our own, so we wrote a simple one, but it was hard-coded')
        body.append('and was a pain to create.  So we came up with the idea to make a program')
        body.append('that could interpret adventure "game files" that were written in a kind')
        body.append('of adventure "language".  So we both wrote programs in')
        body.append('<a href="explore.bas">BASIC</a> to do this')
        body.append('on TRS-80 computers (wow, 1.77 MHz!),')
        body.append('and we wrote adventures in separate text files.  We later merged our work')
        body.append('into this program, which was dubbed "Explore".')
        body.append('By the way, I was really bummed when a guy named')
        body.append('<a href="http://www.msadams.com/index.htm">Scott Adams</a>')
        body.append('(not the Dilbert dude!) came out with a commercial program that')
        body.append('used the same concept!  Just think of all the money <i>we</i> could have made!')
        body.append('<p>')
        body.append('We came up with three adventures that were written')
        body.append('in the wee hours of the morning on three separate occasions listening')
        body.append('to Steely Dan.  It was kind of a mystical inspiration I would say.')
        body.append('<p>')
        body.append('Years later I dug up the old BASIC program and rewrote it in')
        body.append('C (note that the C version and the')
        body.append('BASIC version are no longer being maintained, so future adventure game files')
        body.append('or newer revisions of the old ones won\'t work with the old code).')
        body.append('<p>')
        body.append('A few years after this I rewrote the whole system in Java')
        body.append('as a way to learn the language.  And years after that, I rewrote the')
        body.append('whole thing in Python.  Now, as a way to explore the new languange called')
        body.append('"Ruby", I translated the Python code to Ruby.')
        body.append('Both Python and Ruby versions are now maintained, and either may be used here.')
        body.append('Now you too can play these historic games on-line!')
        body.append('<p>')
        body.append('When starting a')
        body.append('game, you have to pick an adventure.  Your choices are:')
        body.append('')
        body.append('<ul>')
        body.append('')
        body.append('<li><b>Cave</b> - "Enchanted Cave" was the first of our adventure games.')
        body.append('The fact that it takes place in a cave, like the original Adventure, was no')
        body.append('coincidence.  This adventure had lots of rooms, but the capabilities of the')
        body.append('Explore Adventure Language were just being developed, so even though I think')
        body.append('this one came out pretty well, it\'s not as rich in features as the later ones.')
        body.append('')
        body.append('<li><b>Mine</b> - "Lost Mine" takes place in an old coal mine')
        body.append('in a desert environment,')
        body.append('complete with scary skeletons, mining cars, and lots of magic.  We started to')
        body.append('get a little more descriptive in this one, and we also added features to')
        body.append('the adventure language to make things seem a little "smarter."')
        body.append('')
        body.append('<li><b>Castle</b> - "Medieval Castle" was the final in the "trilogy"')
        body.append('of our late-nite')
        body.append('teenage adventure creativity.  This one forced us to add even more features to')
        body.append('the language, and I believe it really became "sophisticated" with this one.')
        body.append('Castle is perhaps the most colorful of the adventures, but not as mystical')
        body.append('somehow as Enchanted Cave.  De and I didn\'t make any more games after this one.')
        body.append('')
        body.append('<li><b>Haunt</b> - "Haunted House" was not an original creation.  It is a clone')
        body.append('of Radio Shack\'s')
        body.append('<a href="http://www.simology.com/smccoy/trs80/model134/mhauntedhouse.html">')
        body.append('Haunted House</a> adventure game that I re-created in the Explore Adventure')
        body.append('Language as a test of the language\'s power.  I had to play the original quite')
        body.append('a bit to get it right, since I was going on the behavior of the game and not')
        body.append('its code.')
        body.append('')
        body.append('<li><b>Porkys</b> - "Porky\'s" is the only one in which I had no involvement.')
        body.append('A friend')
        body.append('in Oklahoma at the time took the Explore language and created this one,')
        body.append('inspired')
        body.append('by the movie of the same name.  It was especially cool to play and solve')
        body.append('an adventure written by someone else with my own adventure language!')
        body.append('Warning, this one has "ADULT CONTENT AND LANGUAGE!"')
        body.append('</ul>')
        body.append('')
        body.append('<hr>')
        body.append('')
        body.append('Other text adventure related links:')
        body.append('<ul>')
        body.append('<li> <a href="http://www.rickadams.org/adventure/">The Colossal Cave Adventure Page</a>')
        body.append('<li> <a href="http://www.plugh.com/">A hollow voice says "Plugh".</a>')
        body.append('<li> <a href="http://www.msadams.com/index.htm">Scott Adams\' Adventure game writer home page</a>')
        body.append('</ul>')
        
    #body.append('')
    body.append('<p>')
    body.append('<table width=100%>')
    body.append('<tr>')
    body.append('<td align=right><i><a href="http://www.wildlava.com/">www.wildlava.com</a></i></td>')
    body.append('</tr>')
    body.append('</table>')

    req.content_type = 'text/html'
    req.send_http_header()
    req.write('<html>\n')
    req.write('<head>\n')
    req.write('<title>The "Explore" Adventure Series</title>\n')
    req.write('</head>\n')
    req.write('<body bgcolor=#aa8822>\n')
    for body_line in body:
        req.write(body_line + '\n')
    req.write('</body>\n')
    req.write('</html>\n')

    session.save()

    return apache.OK
