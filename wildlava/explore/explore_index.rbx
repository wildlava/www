#!/usr/bin/env ruby

require "cgi"
require "cgi/session"

def explore_log(s, ip)
  File.open("/home/html/explore_logs/log", "a") do |fp|
    fp.printf("[%s %s] %s\n",
              Time.now.gmtime.strftime("%Y-%m-%d %H:%M:%S"),
              ip, s)
  end
end

SCREEN_LINES = 16

cgi = CGI.new("html4")
session = CGI::Session.new(cgi,
                           "session_key" => "explore_session",
                           "prefix" => "explore_session-")

# Initialize the page body
out_attr = {}
body = []
body_attr = {"bgcolor" => "#aa8822"}

# Note: session variables return nil if unset, whereas cgi (form/request)
# variables return "" if unset.
advname = session["advname"]
state = session["state"]
last_prompt = session["prompt"]
screen_buffer = session["screen_buffer"]
if screen_buffer
  screen_buffer = screen_buffer.split("\n", -1)
end

if cgi.has_key?("advname")
  new_advname = cgi["advname"]
else
  new_advname = nil
end

if cgi.has_key?("command")
  command = cgi["command"]
else
  command = nil
end

#screen_save_lines = session["screen_save_lines"]
#if screen_save_lines
#  screen_save_lines = screen_save_lines.to_i
#else
#  screen_save_lines = SCREEN_LINES
#end

output_buffer = []
has_suspended_game = false

if new_advname
  # Check for bad characters in name, which could be a security issue
  # when the name is passed as part of a command argument (also
  # potentially a problem when making the cookie name).
  if new_advname != ""
    valid = true
    new_advname.split("").each do |c|
      if c < "a" or c > "z"
        valid = false
        break
      end
    end
  else
    valid = false
  end
  
  if valid
    advname = new_advname
    session["advname"] = advname
  else
    advname = nil
    session["advname"] = nil
  end
end

if advname
  suspend_cookie = cgi.cookies["explore_suspended_game_#{advname}"]
  if suspend_cookie.length > 0
    # Need to quote and escape quotes (used to use "'\\\\''" - wrong)
    suspend_param = " -s '" + suspend_cookie[0].gsub(/'/, "\\\\'") + "'"
    has_suspended_game = true
  else
    suspend_param = ""
  end
  
  if command
    #fp = IO.popen("python /home/html/explore_files/explore.py -c '" + command.gsub(/'/, "\\\\'") + "'" + " -f /home/html/explore_files/#{advname}.exp -r '" + state.gsub(/'/, "\\\\'") + "'" + suspend_param, "r")
    fp = IO.popen("ruby /home/html/explore_files/explore.rb -c '" + command.gsub(/'/, "\\\\'") + "' -f /home/html/explore_files/#{advname}.exp -r '" + state.gsub(/'/, "\\\\'") + "'#{suspend_param}")
    
    if last_prompt
      output_buffer << last_prompt + command
    else
      output_buffer << "?" + command
    end

    explore_log("In game: #{advname} - Issuing command: #{command}", cgi.remote_addr)
  else
    # Clear screen
    screen_buffer = nil
    
    #fp = popen("python /home/html/explore_files/explore.py --one-shot -f /home/html/explore_files/#{advname}.exp" + suspend_param, "r")
    fp = IO.popen("ruby /home/html/explore_files/explore.rb --one-shot -f /home/html/explore_files/#{advname}.exp" + suspend_param)
    
    explore_log("Starting game: #{advname}", cgi.remote_addr)
  end
  
  state = nil
  prompt = nil
  won = false
  dead = false
  quit = false
  
  fp.each do |line|
    line.chomp!
    
    if line.length == 0
      output_buffer << " "
    else
      if line[0,1] == "%"
        if line[1,7] == "PROMPT="
          prompt = line[8..-1]
        elsif line[1,6] == "STATE="
          state = line[7..-1]
        elsif line[1,3] == "WIN"
          won = true
        elsif line[1,3] == "DIE"
          dead = true
        elsif line[1,3] == "END"
          quit = true
        elsif line[1,7] == "SUSPEND" and state
          out_attr["cookie"] = [CGI::Cookie.new("name" => "explore_suspended_game_#{advname}", "value" => [state], "expires" => Time.now + 60*60*24*30)]
        end
      else
        output_buffer << line
      end
    end
  end
  
  fp.close
  
  session["state"] = state
  session["prompt"] = prompt
  if prompt
    output_buffer << prompt
  end
else
  screen_buffer = nil
  
  output_buffer << "No adventure selected."
  output_buffer << " "
  output_buffer << " "
  output_buffer << " "
  output_buffer << " "
  output_buffer << " "
end

# Ready screen for new output
num_output_lines = output_buffer.length
if not screen_buffer
  # Clear screen
  screen_buffer = Array.new(SCREEN_LINES - num_output_lines, " ")
else
  # Move lines up on screen
  if last_prompt
    screen_buffer.slice!(0, num_output_lines - 1)
    screen_buffer.delete_at(-1)
  else
    screen_buffer.slice!(0, num_output_lines)
  end
end

# Add new output lines to screen
screen_buffer += output_buffer

body << "<center>"

body << cgi.h1 { 'The "Explore" Adventure Series' }

# Display screen
body << '<table width=70% cellpadding=5><tr><td colspan=2 bgcolor="#303030" NOWRAP><pre><font color=lightgreen>'

screen_buffer.each { |line| body << line }

body << '</font></pre></td></tr><tr><td colspan=2 bgcolor="#00aacc">'

if not advname
  body << "Please select a game from the list below..."
elsif won
  body << "Congratulations!  You solved the adventure!"
  explore_log("Won game: #{advname}", cgi.remote_addr)
elsif dead
  body << "Game over."
  explore_log("Died in game: #{advname}", cgi.remote_addr)
elsif quit
  body << "Game over."
  explore_log("Quit game: #{advname}", cgi.remote_addr)
else
  # Present command form to user
  body << '<form id="command_form" name="command_form" method=post action="explore.rbx">'
  body << '<input id=command_field size=40 name="command" value="">'
  body << '<input type=submit name="enter" value="Enter">'
  body << "</form>"
  
  # Put focus in command field
  body << '<script type="text/javascript">'
  body << "document.command_form.command_field.focus();"
  body << "</script>"
end

body << '</td></tr><tr><td bgcolor="#00aacc">'
body << "To start a new game, click one of the following:<p>"
body << '<a href="/explore/explore.rbx?advname=cave">cave</a><br>'
body << '<a href="/explore/explore.rbx?advname=mine">mine</a><br>'
body << '<a href="/explore/explore.rbx?advname=castle">castle</a><br>'
body << '<a href="/explore/explore.rbx?advname=haunt">haunt</a><br>'
body << '<a href="/explore/explore.rbx?advname=porkys">porkys</a>'

body << '</td><td bgcolor="#00aacc">'

if has_suspended_game
  body << '<b><font color="#aa4411">You have a suspended game.</font></b><br>To resume, type "resume".<p>'
end

body << 'To save a game, type "suspend".<p>'
body << '<font size=-1>Typing "help" will list some frequently used commands, but remeber that there are many other possible commands to try (things like "get lamp" or "eat taco").  If you are having trouble, try stating it differently or using fewer words.</font>'

body << "</td></tr></table>"

session["screen_buffer"] = screen_buffer.join("\n")

body << "</center>"

if not advname
  body << '<hr>'
  body << ''
  body << 'When I was 15 or so, my cousin, De, and I were into playing adventure games,'
  body << 'like the mother of all text adventure games,'
  body << '"<a href="http://www.rickadams.org/adventure/">Adventure</a>".'
  body << 'We wanted to make our own, so we wrote a simple one, but it was hard-coded'
  body << 'and was a pain to create.  So we came up with the idea to make a program'
  body << 'that could interpret adventure "game files" that were written in a kind'
  body << 'of adventure "language".  So we both wrote programs in'
  body << '<a href="explore.bas">BASIC</a> to do this'
  body << 'on TRS-80 computers (wow, 1.77 MHz!),'
  body << 'and we wrote adventures in separate text files.  We later merged our work'
  body << 'into this program, which was dubbed "Explore".'
  body << 'By the way, I was really bummed when a guy named'
  body << '<a href="http://www.msadams.com/index.htm">Scott Adams</a>'
  body << '(not the Dilbert dude!) came out with a commercial program that'
  body << 'used the same concept!  Just think of all the money <i>we</i> could have made!'
  body << '<p>'
  body << 'We came up with three adventures that were written'
  body << 'in the wee hours of the morning on three separate occasions listening'
  body << 'to Steely Dan.  It was kind of a mystical inspiration I would say.'
  body << '<p>'
  body << 'Years later I dug up the old BASIC program and rewrote it in'
  body << 'C (note that the C version and the'
  body << 'BASIC version are no longer being maintained, so future adventure game files'
  body << 'or newer revisions of the old ones won\'t work with the old code).'
  body << '<p>'
  body << 'A few years after this I rewrote the whole system in Java'
  body << 'as a way to learn the language.  And years after that, I rewrote the'
  body << 'whole thing in Python.  Now, as a way to explore the new languange called'
  body << '"Ruby", I translated the Python code to Ruby.'
  body << 'Now you too can play these historic games on-line (Ruby version)!'
  body << '<p>'
  body << 'When starting a'
  body << 'game, you have to pick an adventure.  Your choices are:'
  body << ''
  body << '<ul>'
  body << ''
  body << '<li><b>Cave</b> - "Enchanted Cave" was the first of our adventure games.'
  body << 'The fact that it takes place in a cave, like the original Adventure, was no'
  body << 'coincidence.  This adventure had lots of rooms, but the capabilities of the'
  body << 'Explore Adventure Language were just being developed, so even though I think'
  body << 'this one came out pretty well, it\'s not as rich in features as the later ones.'
  body << ''
  body << '<li><b>Mine</b> - "Lost Mine" takes place in an old coal mine'
  body << 'in a desert environment,'
  body << 'complete with scary skeletons, mining cars, and lots of magic.  We started to'
  body << 'get a little more descriptive in this one, and we also added features to'
  body << 'the adventure language to make things seem a little "smarter."'
  body << ''
  body << '<li><b>Castle</b> - "Medieval Castle" was the final in the "trilogy"'
  body << 'of our late-nite'
  body << 'teenage adventure creativity.  This one forced us to add even more features to'
  body << 'the language, and I believe it really became "sophisticated" with this one.'
  body << 'Castle is perhaps the most colorful of the adventures, but not as mystical'
  body << 'somehow as Enchanted Cave.  De and I didn\'t make any more games after this one.'
  body << ''
  body << '<li><b>Haunt</b> - "Haunted House" was not an original creation.  It is a clone'
  body << 'of Radio Shack\'s'
  body << '<a href="http://www.simology.com/smccoy/trs80/model134/mhauntedhouse.html">'
  body << 'Haunted House</a> adventure game that I re-created in the Explore Adventure'
  body << 'Language as a test of the language\'s power.  I had to play the original quite'
  body << 'a bit to get it right, since I was going on the behavior of the game and not'
  body << 'its code.'
  body << ''
  body << '<li><b>Porkys</b> - "Porky\'s" is the only one in which I had no involvement.'
  body << 'A friend'
  body << 'in Oklahoma at the time took the Explore language and created this one,'
  body << 'inspired'
  body << 'by the movie of the same name.  It was especially cool to play and solve'
  body << 'an adventure written by someone else with my own adventure language!'
  body << 'Warning, this one has "ADULT CONTENT AND LANGUAGE!"'
  body << '</ul>'
  body << ''
  body << '<hr>'
  body << ''
  body << 'Other text adventure related links:'
  body << '<ul>'
  body << '<li> <a href="http://www.rickadams.org/adventure/">The Colossal Cave Adventure Page</a>'
  body << '<li> <a href="http://www.plugh.com/">A hollow voice says "Plugh".</a>'
  body << '<li> <a href="http://www.msadams.com/index.htm">Scott Adams\' Adventure game writer home page</a>'
  body << '</ul>'
end

body << ''
body << '<p>'
body << '<table width=100%>'
body << '<tr>'
body << '<td align=right><i><a href="http://www.skyrush.com/">skyrush.com</a></i></td>'
body << '</tr>'
body << '</table>'

cgi.out(out_attr) { cgi.html { "\n" + cgi.head { "\n" + cgi.title { 'The "Explore" Adventure Series' } + "\n" } + "\n" + cgi.body(body_attr) { "\n" + body.join("\n")  + "\n" } + "\n" } + "\n" }

session.close

# Clean up session files older than 24 hours
#system 'find /tmp -amin +1440 -name "explore_session-*" | xargs rm'
