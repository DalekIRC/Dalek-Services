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
\\	Title: RAW
//	
\\	Desc: Raw commands lmao
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


operserv::func("privmsg", function($u){

	global $os,$operserv;
	$nick = new User($u['nick']);
	if (!$nick->IsUser)
	{
		return;
	}
	
	if (!$nick->account)
	{
		$os->notice($nick->uid,"Access denied!");
		return;
	}

	if ($nick->account !== $operserv['oper'])
	{
		$os->notice($nick->uid,"Access denied!");
		return;
	}
	
	
	
	$parv = explode(" ",$u['msg']);
	
	if ($parv[0] !== "raw"){ return; }
	
	$raw = str_replace($parv[0]." ","",$u['msg']);
	
	$os->sendraw($raw);
});
