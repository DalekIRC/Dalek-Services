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
\\	Title:		HostServ 
//				
\\	Desc:		Provides the bare essentials for
//				pseudoclient HostServ, the
\\				vHost Service
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

// hostserv configuration
include "class.php";
include "hostserv.conf";
include "modules.conf";


// Spawn hostserv on server connect
hook::func("connect", function($u){
		global $hostserv,$hs;
		
		// spawn client with $hs
		$hs = new Client($hostserv['nick'],$hostserv['ident'],$hostserv['hostmask'],$hostserv['uid'],$hostserv['gecos']);
		
});


hook::func("privmsg", function($u){
	
	global $hs;
	if (strpos($u['dest'],"@") !== false){
		$n = explode("@",$u['dest']);
		$dest = $n[0];
	}
	else { $dest = $u['dest']; }
	
	
	if (strtolower($dest) == strtolower($hs->nick)){ 
		hostserv::run("privmsg", array(
			"msg" => $u['parv'],
			"nick" => $u['nick'])
		);
			
	}
	
});
