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
\\	Title:		OperServ
//				
\\	Desc:		Provides the bare essentials for
//				pseudoclient OperServ, the
\\				Operator Service.
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

// operserv configuration
include "class.php";
include "operserv.conf";
include "modules.conf";


// Spawn operserv on server connect
hook::func("connect", function($u){
		global $operserv,$os;
		
		// spawn client with $os
		$os = new Client($operserv['nick'],$operserv['ident'],$operserv['hostmask'],$operserv['uid'],$operserv['gecos']);
		
});


hook::func("privmsg", function($u){
	global $os;
	if (strpos($u['dest'],"@") !== false){
		$n = explode("@",$u['dest']);
		$dest = $n[0];
	}
	else { $dest = $u['dest']; }
	
	
	if ((strtolower($dest) == strtolower($os->nick)) || (($dest == $os->uid))){ 
		operserv::run("privmsg", array(
			"msg" => $u['parv'],
			"nick" => $u['nick'])
		);
			
	}
	
});
