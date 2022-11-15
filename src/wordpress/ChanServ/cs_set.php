<?php
/*				
//	(C) 2021 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: Set
//	
\\	Desc: Base command for set options
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
	
	if ($parv[0] == "set"){
		
		if (!isset($parv[1])){
			chanserv::run("setlist", array('nick' => $nick));
		}
		else { chanserv::run("setcmd", array('nick' => $nick, 'cmd' => $u['msg'])); }
		
	}
});

chanserv::func("helplist", function($u){
	
	global $cs;
	
	$nick = $u['nick'];
	
	$cs->notice($nick,"SET				 Set options for your channel.");
});


chanserv::func("help", function($u){
	
	global $cs;
	
	if ($u['key'] !== "set"){ return; }
	if (!isset($u['string'])){ $u['string'] = NULL; }
	$nick = $u['nick'];
	chanserv::run("setlist", array('nick' => $nick));
});