<?php
/*
 *	(C) 2022 Dalek IRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: 
 *
 * 
 *	Version: 1
*/


chanserv::func("privmsg", function($u)
{
	global $cs;
	
	$parv = explode(" ",$u['msg']);
	if ($parv[0] !== "invite")
		return;
	
	$nick = new User($u['nick']);
	$wpnick = new WPUser($u['nick']);
	if (!$nick->account)
	{
		$eu->notice($nick->uid,"You need to login use that command.");
		return;
	}
	
	if (!isset($parv[1]))
	{
		$cs->notice($nick->uid,"Syntax: /msg $cs->nick INVITE <#channel>");
		return;
	}
	$chan = new Channel($parv[1]);
	
	if (!$chan->IsChan || !$chan->IsReg)
	{
		$cs->notice($nick->uid,"That channel is not registered.");
		return;
	}
	
	if ($t = $chan->HasUser($nick->uid))
	{
		$cs->notice($nick->uid,"You are already on that channel");
		return;
	}
	
	if (!ChanAccessAsInt($chan,$nick))
	{
		$cs->notice($nick->uid,"Permission denied.");
		return;
	}
	
	$cs->invite($nick,$chan);
	$cs->notice($nick->uid,"You have been invited to join $chan->chan");
});