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
\\	Title: OperServ Send Umode
//	
\\	Desc: Send a usermode to a nick lol
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
	
	if ($parv[0] !== "sendumode")
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
	
	if (!($target = new User($parv[1]))->IsUser)
	{
		$os->notice($nick->uid,"User not found.");
		return;
	}
	if (!isset($parv[2]))
	{
		$os->notice($nick->uid,"Not enough parameters.");
		return;
	}
	if (!$target->SetMode($parv[2]))
	{
		$os->notice($target->uid,"Invalid mode chars.");
	}
});
	
