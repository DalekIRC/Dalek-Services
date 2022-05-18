<?php
/*
 *	(C) 2022 Dalek IRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: DEVOICE command
 *
 * 
 *	Version: 1
*/



hook::func("privmsg", function($u)
{
	$tok = explode(" ",$u['parv']);
	if ($tok[0] !== "!devoice")
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
	
	if ($parv[0] !== "devoice")
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
		$cs->notice($nick->uid,"Syntax: /msg $cs->nick DEVOICE <chan> [<nick>]");
		return;
	}
	if (!$chan->IsVoice($target->uid))
	{
		$cs->notice($nick->uid,"$target->nick is not voiced on that channel.");
		return;
	}
	if (can_devoice($nick->account,$chan->chan))
		$cs->mode($chan->chan,"-v $target->nick");
	return;
});


function can_devoice($nick,$chan)
{
	global $cs;
	if (IsAdmin(wp_get_caps($nick)))
		return true;
		
	else {
		$channel = new Channel($chan);
		$access = ChanAccess($channel,$nick);
		if ($access == "owner" || $access == "operator")
			return true;
	}
	return false;
}
