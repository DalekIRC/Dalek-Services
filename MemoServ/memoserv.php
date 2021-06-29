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
\\	Title:		MemoServ
//				
\\	Desc:		Provides the bare essentials for
//				pseudoclient MemoServ, the
\\				Memo Service.
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

// memoserv configuration
include "class.php";
include "memoserv.conf";
include "modules.conf";


// Spawn memoserv on server connect
hook::func("connect", function($u){
		global $memoserv,$ms;
		
		// spawn client with $ms
		$ms = new Client($memoserv['nick'],$memoserv['ident'],$memoserv['hostmask'],$memoserv['uid'],$memoserv['gecos']);
		
});


hook::func("privmsg", function($u){
	
	global $ms;
	if (strpos($u['dest'],"@") !== false){
		$n = explode("@",$u['dest']);
		$dest = $n[0];
	}
	else { $dest = $u['dest']; }
	
	
	if (strtolower($dest) == strtolower($ms->nick)){ 
		memoserv::run("privmsg", array(
			"msg" => $u['parv'],
			"nick" => $u['nick'])
		);
			
	}
	
});
