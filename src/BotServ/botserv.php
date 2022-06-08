<?php
/*				
//	(C) 2021 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\							v3
//				
\\				
//				
\\	Title:		BotServ
//				
\\	Desc:		Provides the bare essentials for
//				pseudoclient BotServ, the
\\				Channel Bot Service.
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

// botserv configuration
include "class.php";
include "botserv.conf";
include "modules.conf";


// Spawn botserv on server connect
hook::func("connect", function($u){
		global $botserv,$bs;
		
		// spawn client with $bs
		$bs = new Client($botserv['nick'],$botserv['ident'],$botserv['hostmask'],$botserv['uid'],$botserv['gecos']);
		
});


hook::func("privmsg", function($u){
	
	global $bs;
	if (strpos($u['dest'],"@") !== false){
		$n = explode("@",$u['dest']);
		$dest = $n[0];
	}
	else { $dest = $u['dest']; }
	
	
	if (strtolower($dest) == strtolower($bs->nick)){ 
		botserv::run("privmsg", array(
			"msg" => $u['parv'],
			"nick" => $u['nick'])
		);
			
	}
	
});
