<?php
/*
 *	(C) 2021 Pride IRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: DEOP command
 *
 * 
 *	Version: 1
*/



hook::func("privmsg", function($u)
{
	$tok = explode(" ",$u['parv']);
	
	if ($tok[0] !== "!deop")
		return;
	if ($u['dest'][0] !== "#")
		return;
	
	$params = rparv($u['parv']);
	$params = (strlen($params) == 0) ? " ".$u['dest'] : " ".$u['dest']." ".$params;
	
	$command = str_replace("!","",$tok[0]);
	
	chanserv::run("privmsg", array(
		'msg' => $command.$params,
		'nick' => $u['nick'])
	);

});

chanserv::func("privmsg", function($u)
{
	global $cs;
	$parv = explode(" ",$u['msg']);
	
	if ($parv[0] !== "deop")
		return;
	
	$nick = new User($u['nick']);
	if (!$nick->account)
	{
		$cs->notice($nick->uid,"You need to login to use that command.");
		return;
	}
	
	$chan = (isset($parv[1])) ? new Channel($parv[1]) : false;
	
	$target = (isset($parv[2])) ? new User($parv[2]) : $nick;

	if (!$chan)
	{
		$cs->notice($nick->uid,"Syntax: /msg $cs->nick DEOP <chan> [<nick>]");
		return;
	}
	if ($chan->IsOp($target->uid) == false)
	{
		$cs->notice($nick->uid,"$target->nick is not opped on that channel.");
		return;
	}
	if (can_deop($nick->account,$chan->chan))
		$cs->mode($chan->chan,"-o $target->nick");
	return;
});


function can_deop($nick,$chan)
{
	global $cs;
	if (IsAdmin(wp_get_caps($nick)))
		return true;
		
	else {
		$channel = new Channel($chan);
		$access = ChanAccess($channel,$nick);
		if ($access == "owner" || $access == "admin" || $access == "operator")
			return true;
	}
	return false;
}