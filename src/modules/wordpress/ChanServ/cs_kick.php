<?php
/*
 *	(C) 2022 Dalek IRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: KICK command
 *
 * 
 *	Version: 1
 */



hook::func("privmsg", function($u)
{
	$tok = explode(" ",$u['parv']);
	
	if ($tok[0] !== "!kick")
		return;
	if ($u['dest'][0] !== "#")
		return;
	
	$params = rparv($u['parv']);
	$params = (strlen($params) == 0) ? " ".$u['dest'] : " ".$u['dest']." ".$params;
	$command = str_replace("!","",$tok[0]);
	echo($command.$params);
	chanserv::run("privmsg", array(
		'msg' => $command.$params,
		'nick' => $u['nick'])
	);

});
chanserv::func("privmsg", function($u)
{
	global $cs;
	
	$parv = explode(" ",$u['msg']);
	
	if ($parv[0] !== "kick")
		return;
	
	$nick = new User($u['nick']);
	
	if (!isset($parv[2]))
		return;
	$chan = new Channel($parv[1]);
	$target = new User($parv[2]);
	
	if (!$chan->IsChan)
	{
		$cs->notice($nick->uid,"Channel doesn't exist.");
		return;
	}
	
	if (!$target->IsUser)
	{
		$cs->notice($nick->uid,"User doesn't exist.");
		return;
	}
	
	if (!($chaccess = ChanAccess($chan,$nick->nick)))
	{
		$cs->notice($nick->uid,"Permission denied.");
		return;
	}
	
	if (!$chan->HasUser($target->uid))
	{
		$cs->notice($nick->uid,"User is not on that channel.");
		return;
	}
	
	if (!can_kick($chan,$nick,$target))
	{
		$cs->notice($nick->uid,"Permission denied.");
		return;
	}
	
	$params = rparv(rparv(rparv($u['msg'])));
	if (strlen($params) == 0)
		$params = "[$nick->nick]";
	else
		$params .= " [$nick->nick]";
	
	$cs->log("$nick->nick used KICK to kick user $target->nick from channel $chan->chan");
	$cs->kick($chan->chan,$target->nick,$params);
});


function can_kick(Channel $chan, User $nick, User $target)
{
	$user_access = ChanAccessAsInt($chan,$nick);
	$target_access = ChanAccessAsInt($chan,$target);
	
	$return = ($user_access > $target_access) ? true : false;
	
	return $return;
}
	
	
	