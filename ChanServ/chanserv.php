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
\\	Title:		ChanServ
//				
\\	Desc:		Provides the bare essentials for
//				pseudoclient ChanServ, the
\\				Channel Registration Service.
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

// chanserv configuration
include "class.php";
include "chanserv.conf";
include "modules.conf";


// Spawn chanserv on server connect
hook::func("connect", function($u){
		global $chanserv,$cs;
		
		// spawn client with $cs
		$cs = new Client($chanserv['nick'],$chanserv['ident'],$chanserv['hostmask'],$chanserv['uid'],$chanserv['gecos']);
		
});


hook::func("privmsg", function($u){
	
	global $cs;
	if (strpos($u['dest'],"@") !== false){
		$n = explode("@",$u['dest']);
		$dest = $n[0];
	}
	else { $dest = $u['dest']; }
	
	
	if (strtolower($dest) == strtolower($cs->nick)){ 
		chanserv::run("privmsg", array(
			"msg" => $u['parv'],
			"nick" => $u['nick'])
		);
			
	}
	
});

	