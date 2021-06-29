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
\\	Title:		Global
//				
\\	Desc:		Provides the bare essentials for
//				pseudoclient Global, the
\\				Global Noticer Service.
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

// global configuration
include "class.php";
include "global.conf";
include "modules.conf";


// Spawn global on server connect
hook::func("connect", function($u){
		global $global,$gb;
		
		// spawn client with $gb
		$gb = new Client($global['nick'],$global['ident'],$global['hostmask'],$global['uid'],$global['gecos']);
		
});


hook::func("privmsg", function($u){
	
	global $gb;
	if (strpos($u['dest'],"@") !== false){
		$n = explode("@",$u['dest']);
		$dest = $n[0];
	}
	else { $dest = $u['dest']; }
	
	
	if (strtolower($dest) == strtolower($gb->nick)){ 
		globa::run("privmsg", array(
			"msg" => $u['parv'],
			"nick" => $u['nick'])
		);
			
	}
	
});

	