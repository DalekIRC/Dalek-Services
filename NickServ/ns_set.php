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

nickserv::func("privmsg", function($u){
	
	global $ns;
	
	$parv = explode(" ",$u['msg']);
	$nick = $u['nick'];
	
	if ($parv[0] == "set"){
		
		if (!isset($parv[1])){
			nickserv::run("setlist", array('nick' => $nick));
		}
		else { nickserv::run("setcmd", array('nick' => $nick, 'cmd' => $u['msg'])); }
		
	}
});

nickserv::func("helplist", function($u){
	
	global $ns;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"SET                 Set options for your account.");
});


nickserv::func("help", function($u){
	
	global $ns;
	
	if ($u['key'] !== "set"){ return; }
	if (!isset($u['string'])){ $u['string'] = NULL; }
	$nick = $u['nick'];
	nickserv::run("setlist", array('nick' => $nick));
});