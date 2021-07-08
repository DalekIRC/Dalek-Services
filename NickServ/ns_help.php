<?php

/*				
//	(C) 2021 DalekIRC Services
\\				
//			dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: Help
//	
\\	Desc: Provides the help command and hook for NickServ
//	
\\	
//	
\\	
//	
\\	Version: 1
//				
\\	Author:	Valware
//				
*/




nickserv::func("privmsg", function($u){
	
	global $ns;
	
	$parv = explode(" ",$u['msg']);
	$nick = $u['nick'];
	
	if ($parv[0] == "help"){
		if (!isset($parv[1])){
		
			$ns->notice($nick,"NickServ allows you to register and retain");
			$ns->notice($nick,"ownership of a username and enforce certain");
			$ns->notice($nick,"settings for your account.");
			$ns->notice($nick," ");
			$ns->notice($nick,"Here is a list of commands available to you:");
			$ns->notice($nick," ");
			
			nickserv::run("helplist", array('nick' => $nick));
			
			$ns->notice($nick," ");
			$ns->notice($nick,"For more information on a command, type:");
			$ns->notice($nick,"/msg $ns->nick help command");
		}
		else { nickserv::run("help", array('nick' => $nick, 'key' => $parv[1])); }
		
	}
});


nickserv::func("helplist", function($u){
	
	global $ns;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"HELP                Show this list and gets help on a specific command.");
	
});



nickserv::func("help", function($u){
	
	global $ns;
	
	if ($u['key'] !== "help"){ return; }
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"Command: HELP");
	$ns->notice($nick,"Syntax: /msg $ns->nick help command");
	$ns->notice($nick,"Example: /msg $ns->nick help register");
});
