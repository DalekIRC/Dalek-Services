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
\\	Title: Operserv SVSNICK
//	
\\	Desc: Change a nicks nick
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
	
	global $os,$serv,$operserv;
	
	$parv = explode(" ",$u['msg']);
	
	$Nick2Change = $parv[1] ?? false;
	$NickChange2 = $parv[2] ?? false;
	
	if ($parv[0] !== "svsnick")
	{
		return;
	}
	
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
	
	if (!$Nick2Change || !$NickChange2)
	{
		$os->notice($nick->uid,"Invalid paramaters.");
		return;
	}
	
	$Nick2Change = new User($Nick2Change);
	
	if (!$Nick2Change->IsUser)
	{
		$os->notice($nick->uid,"Could not find user.");
		return;
	}
	
	if (!validate_nick($NickChange2))
	{
		$os->notice($nick->uid,"Invalid new nick.");
		return;
	}
	
	$Nick2Change->NewNick($NickChange2);
});
