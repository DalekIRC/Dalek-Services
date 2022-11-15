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
\\	Desc: Provides the help command and hook for chanserv
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




chanserv::func("privmsg", function($u){
	
	global $cs;
	
	$parv = explode(" ",$u['msg']);
	$nick = $u['nick'];
	if (!($cmd = (isset($parv[2])) ? $parv[1]." ".$parv[2] : ($p2 = isset($parv[1]) ? $parv[1] : NULL))){ $cmd = NULL; }
	if ($parv[0] == "help"){
		if (!$cmd){
		
			$cs->notice($nick,"$cs->nick allows you to register and retain");
			$cs->notice($nick,"ownership of a channel and enforce certain");
			$cs->notice($nick,"settings for your channel.");
			$cs->notice($nick," ");
			$cs->notice($nick,"Here is a list of commands available to you:");
			$cs->notice($nick," ");
			
			chanserv::run("helplist", array('nick' => $nick));
			
			$cs->notice($nick," ");
			$cs->notice($nick,"For more information on a command, type:");
			$cs->notice($nick,"/msg $cs->nick help command");
		}
		else { chanserv::run("help", array('nick' => $nick, 'key' => $cmd, 'string' => str_replace($cmd." ","",$u['msg']))); }
		
	}
});


chanserv::func("helplist", function($u){
	
	global $cs;
	
	$nick = $u['nick'];
	
	$cs->notice($nick,"HELP				Show this list and gets help on a specific command.");
	
});



chanserv::func("help", function($u){
	
	global $cs;
	
	if ($u['key'] !== "help"){ return; }
	
	$nick = $u['nick'];
	
	$cs->notice($nick,"Command: HELP");
	$cs->notice($nick,"Syntax: /msg $cs->nick help command");
	$cs->notice($nick,"Example: /msg $cs->nick help register");
});
