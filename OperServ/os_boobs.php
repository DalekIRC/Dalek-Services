<?php

// This modules provides command "LOL" which returns some stupid stuff

nickserv::func("privmsg", function($u){
  global $ns;
  
  $parv = explode(" ",$u['msg']);
  
  if (!($nick = new User($u['nick'])))
  {
    return;
  }
  
  if ($parv[0] !== "lol")
  {
    $ns->notice($nick-uid,"LOL at your fuckin self lmao");
  }
});
