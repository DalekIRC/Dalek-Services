<?php
/*
 *	(C) 2021 Pride IRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: AUTOVOICE
 *
 * 
 *	Version: 1
*/

hook::func("join", function($u)
{
	global $cs;
	
	$nick = $u['nick'];
	$chan = $u['chan'];
	
	if (!$nick->account)
		return;
	
	$wpuser = new WPUser($nick->account);
	$chan = new Channel($chan);
	if (is_autovoice($wpuser,$chan->chan) && !$chan->IsVoice($nick->nick))
	{
		$cs->mode($chan->chan,"+v $nick->nick");
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
		if (is_autovoice($user,$chan->chan) && !$chan->IsVoice($nick->nick))
			$cs->mode($chan->chan,"+v $nick->nick");
});

chanserv::func("privmsg", function($u)
{
	global $cs;
	$parv = explode(" ",$u['msg']);
	
	if ($parv[0] !== "autovoice")
		return;
	
	$nick = new User($u['nick']);
	if (!isset($parv[2]))
	{
		$cs->notice($nick->uid,"Syntax: /msg $cs->nick AUTOVOICE <chan> on|off");
		return;
	}
	$toggle = $parv[2];
	if ($toggle !== "on" && $toggle !== "off")
	{
		$cs->notice($nick->uid,"Syntax: /msg $cs->nick AUTOVOICE <chan> on|off");
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
		$cs->notice($nick->uid,"Syntax: /msg $cs->nick AUTOVOICE <chan> on|off");
		return;
	}
	if (!$chan->IsChan)
	{
		$cs->notice($nick->uid,"That channel does not exist.");
		return;
	}
	if (can_autovoice($nick->nick,$chan))
	{
		$user = new WPUser($nick->account);
		
		if ($toggle == "on" && is_autovoice($user,$chan->chan))
		{
			$cs->notice($nick->uid,"AUTOVOICE is already set to 'on' for $chan->chan");
			return;
		}
		
		elseif ($toggle == "off" && !is_autovoice($user,$chan->chan))
		{
			$cs->notice($nick->uid,"AUTOVOICE is already set to 'off' for $chan->chan");
			return;
		}
		
		autovoice_toggle($user,$chan->chan,$toggle);
		$cs->notice($nick->uid,"AUTOVOICE has been set to '$toggle' for $chan->chan");
		return;
	}
	$cs->notice($nick->uid,"Permission denied.");
});

function can_autovoice($nick,Channel $channel)
{
	global $cs;
		
	$access = ChanAccess($channel,$nick);
	if ($access == "owner" || $access == "operator" || $access == "admin" || $access == "voice")
		return true;
	return false;
}

function is_autovoice(WPUser $nick,$chan)
{
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_account_settings WHERE account = ?");
	$prep->bind_param("s",$nick->login);
	$prep->execute();
	$return = false;
	$result = $prep->get_result();
	if ($result->num_rows == 0)
		return false;
	
	while ($row = $result->fetch_assoc())
	{
		if ($row['setting_key'] == "autovoice_$chan" && $row['setting_value'] == "on")
			$return = true;
	}
	$prep->close();
	return $return;
}

function autovoice_toggle(WPUser $nick,$chan,$toggle)
{
	$conn = sqlnew();
	if (!$conn)
		return;
	
	$setting_key = "autovoice_$chan";
	if (is_autovoice($nick,$chan) == 0)
	{
		$prep = $conn->prepare("INSERT INTO dalek_account_settings (account, setting_key, setting_value) VALUES (?, ?, ?)");
		$prep->bind_param("sss",$nick->login,$setting_key,$toggle);
		$prep->execute();
	}
	else
	{
		$prep = $conn->prepare("UPDATE dalek_account_settings SET setting_key = ?, setting_value = ? WHERE account = ?");
		$prep->bind_param("sss",$setting_key,$toggle,$nick->login);
		$prep->execute();
	}
}


chanserv::func("helplist", function($u){
	
	global $cs;
	
	$nick = $u['nick'];
	
	$cs->notice($nick,"AUTOVOICE           Modify your AUTOVOICE setting for a channel.");
	
});


chanserv::func("help", function($u){
	
	global $cs;
	
	if ($u['key'] !== "autovoice"){ return; }
	
	$nick = $u['nick'];
	
	$cs->notice($nick,"Command: AUTOVOICE");
	$cs->notice($nick,"Syntax: /msg $cs->nick autovoice #channel <on|off>");
	$cs->notice($nick,"Example: /msg $cs->nick autovoicevoice #channel on");
});