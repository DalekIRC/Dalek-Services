<?php
/*
 *	(C) 2021 Dalek IRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: AUTO OP
 *
 * 
 *	Version: 1
*/

hook::func("join", function($u)
{
	global $cs;
	$nick = new User($u['nick']);
	$chan = $u['chan'];
	
	if (!isset($nick->account))
		return;
	
	$wpuser = new WPUser($nick->account);
	$chan = new Channel($chan);
	if (is_autoop($wpuser,$chan->chan) == "on" && !$chan->IsOp($nick->nick))
	{
		$cs->up($chan,$nick);
	}
});

hook::func("user_login", function($nick)
{
	global $cs;
	if (!($list = get_ison($nick->uid)))
		return;
	$user = new WPUser($nick->account);
	foreach ($list['list'] as $chan)
		$chan = new Channel($chan);
		if (is_autoop($user,$chan->chan) == "on" && !$chan->IsOp($nick->nick))
			$cs->mode($chan->chan,"+o $nick->nick");
});

chanserv::func("privmsg", function($u)
{
	global $cs;
	$parv = explode(" ",$u['msg']);
	
	if ($parv[0] !== "autoop")
		return;
	
	$nick = new User($u['nick']);
	if (!isset($parv[2]))
	{
		$cs->notice($nick->uid,"Syntax: /msg $cs->nick AUTOOP <chan> on|off");
		return;
	}
	$toggle = $parv[2];
	if ($toggle !== "on" && $toggle !== "off")
	{
		$cs->notice($nick->uid,"Syntax: /msg $cs->nick AUTOOP <chan> on|off");
		return;
	}
	
	if ($nick->account !== $nick->nick)
	{
		$cs->notice($nick->uid,"You need to login to use that command.");
		return;
	}
	
	$chan = (isset($parv[1])) ? new Channel($parv[1]) : false;

	if (!$chan)
	{
		$cs->notice($nick->uid,"Syntax: /msg $cs->nick AUTOOP <chan> on|off");
		return;
	}
	if (!$chan->IsChan)
	{
		$cs->notice($nick->uid,"That channel does not exist.");
		return;
	}
	if (can_autoop($nick->nick,$chan))
	{
		$user = new WPUser($nick->account);
		
		if ($toggle == "on" && is_autoop($user,$chan->chan) == "on")
		{
			$cs->notice($nick->uid,"AUTOOP is already set to 'on' for $chan->chan");
			return;
		}
		
		elseif ($toggle == "off" && (!$isop = is_autoop($user,$chan->chan) || $isop = "off"))
		{
			$cs->notice($nick->uid,"AUTOOP is already set to 'off' for $chan->chan");
			return;
		}
		
		autoop_toggle($user,$chan->chan,$toggle);
		$cs->notice($nick->uid,"AUTOOP has been set to '$toggle' for $chan->chan");
		return;
	}
	$cs->notice($nick->uid,"Permission denied.");
});

function can_autoop($nick,Channel $channel)
{
	global $cs;
		
	$access = ChanAccess($channel,$nick);
	if ($access == "owner" || $access == "operator" || $access == "admin")
		return true;
	return false;
}

function is_autoop(WPUser $nick,$chan)
{
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_account_settings WHERE account = ?");
	$prep->bind_param("s",$nick->user_login);
	$prep->execute();
	$return = false;
	$result = $prep->get_result();
	if ($result->num_rows == 0)
		return false;
	
	while ($row = $result->fetch_assoc())
	{
		if ($row['setting_key'] == "autoop_$chan")
			$return = $row['setting_value'];
	}
	$prep->close();
	return $return;
}

function autoop_toggle(WPUser $nick,$chan,$toggle)
{
	$conn = sqlnew();
	if (!$conn)
		return;
	
	$setting_key = "autoop_$chan";
	if (!is_autoop($nick,$chan))
	{
		$prep = $conn->prepare("INSERT INTO dalek_account_settings (account, setting_key, setting_value) VALUES (?, ?, ?)");
		$prep->bind_param("sss",$nick->user_login,$setting_key,$toggle);
		$prep->execute();
	}
	else
	{
		$prep = $conn->prepare("UPDATE dalek_account_settings SET setting_key = ?, setting_value = ? WHERE account = ?");
		$prep->bind_param("sss",$setting_key,$toggle,$nick->user_login);
		$prep->execute();
	}
}
