<?php

// This modules provides command "LOL" which returns some stupid stuff

operserv::func("privmsg", function($u){
  global $os;
  
  $parv = explode(" ",$u['msg']);
  
  if (!($nick = new User($u['nick'])))
  {
    return;
  }
  
  if ($parv[0] !== "lol")
  {
    return;
  }
  $os->notice($nick->uid,"LOL at your fuckin self lmao");
});
