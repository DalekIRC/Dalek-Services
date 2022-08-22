<?php
/*
 *	(C) 2022 Dalek IRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: VOICE command
 *
 * 
 *	Version: 1
*/

hook::func("privmsg", function($u)
{
	$tok = explode(" ",$u['parv']);
	
	if ($tok[0] !== "!voice")
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
	
	if ($parv[0] !== "voice")
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
		$cs->notice($nick->uid,"Syntax: /msg $cs->nick VOICE <chan> [<nick>]");
		return;
	}
	if ($chan->IsVoice($target->uid))
	{
		$cs->notice($nick->uid,"$target->nick is already voiced on that channel.");
		return;
	}
	if (can_voice($nick->account,$chan))
		$cs->mode($chan->chan,"+v $target->nick");
	return;
});


function can_voice($nick,Channel $channel)
{
	global $cs;
	if (IsAdmin(wp_get_caps($nick)))
		return true;
		
	else {
		$access = ChanAccess($channel,$nick);
		if ($access == "owner" || $access == "admin" || $access == "operator" || $access == "voice")
			return true;
	}
	return false;
}

	

chanserv::func("helplist", function($u){
	
	global $cs;
	
	$nick = $u['nick'];
	
	$cs->notice($nick,"VOICE               Voices you in a channel.");
	
});


chanserv::func("help", function($u){
	
	global $cs;
	
	if ($u['key'] !== "voice"){ return; }
	
	$nick = $u['nick'];
	
	$cs->notice($nick,"Command: VOICE");
	$cs->notice($nick,"Syntax: /msg $cs->nick voice #channel [nick]");
	$cs->notice($nick,"Example: /msg $cs->nick voice #channel Lamer32");
});