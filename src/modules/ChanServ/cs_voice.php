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
\\	Title: NickServ VOICE command
//	
\\	Desc:	Allows users to voice themselves or others
//
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/

require_module("sjoin");

/* Our class! This is the module itself. It needs to be named the same as the file, without ".php" */
class cs_voice {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "cs_voice";
	public $description = "VOICE";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		/* Lets define our command =] */
		define("CMD_CS_VOICE","VOICE");

	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		hook::del("join", 'cs_voice::hook_join');
		hook::del("auth", 'cs_voice::hook_auth');
	}

	/* This part is the _inititalisation! This is ran when the module has been successfully loaded */
	function __init()
	{
		/* Lets add our command to ChanServ =]
		 * This is where we put our help string, syntax, and extended help for the 'HELP' command output.
		 * Just for a kind of 'all-in-one' thing
		 */
		$help_string = "Voice yourself or someone else on a channel";
		$syntax = "VOICE <channel> [<nick>]";
		$extended_help = "$help_string\n$syntax";

		if (!AddServCmd(
			'cs_voice', /* Module name */
			'ChanServ', /* Client name */
			CMD_CS_VOICE, /* Command */
			'cs_voice::function', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false; /* If something went wrong, we gotta back out and unload the module */

		hook::func("join", 'cs_voice::hook_join');
		hook::func("auth", 'cs_voice::hook_auth');
		return true; /* weeee are good */
	}
	
	function function($u)
	{
		/* Grab our target Client object (ChanServ) */
		$cs = $u['target'];
		
		$nick = $u['nick'];
		$account = $nick->account ?? NULL;
		$parv = explode(" ",$u['msg']);
		if (!$account)
		{
			$cs->notice($nick->uid,"You need to login to use that command.");
			return;
		}
		
		if (!isset($parv[2]))
		{
			$cs->notice($nick->uid,"Syntax: /msg $cs->nick AUTOOP <channel> <on|off>");
			return;
		}
		$toggle = $parv[2];
		if ($toggle !== "on" && $toggle !== "off")
		{
			$cs->notice($nick->uid,"Syntax: /msg $cs->nick AUTOOP <channel> <on|off>");
			return;
		}
		
		
		$chan = (isset($parv[1])) ? new Channel($parv[1]) : false;

		if (!$chan)
		{
			$cs->notice($nick->uid,"Syntax: /msg $cs->nick AUTOOP AUTOOP <channel> <on|off>");
			return;
		}
		if (!$chan->IsChan)
		{
			$cs->notice($nick->uid,$parv[1].": That channel does not exist.");
			return;
		}
		if (cs_voice::can_autoop($account,$chan))
		{
			$user = $nick->wp;
			
			if ($toggle == "on" && cs_voice::is_autoop($user,$chan->chan) == "on")
			{
				$cs->notice($nick->uid,"AUTOOP is already set to 'on' for $chan->chan");
				return;
			}
			
			elseif ($toggle == "off" && (!$isop = cs_voice::is_autoop($user,$chan->chan) || $isop = "off"))
			{
				$cs->notice($nick->uid,"AUTOOP is already set to 'off' for $chan->chan");
				return;
			}
			
			cs_voice::autoop_toggle($user,$chan->chan,$toggle);
			$cs->notice($nick->uid,"AUTOOP has been set to '$toggle' for $chan->chan");
			return;
		}
		$cs->notice($nick->uid,"Permission denied.");
	}

	function can_autoop($nick,Channel $channel)
	{
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
		if (!cs_voice::is_autoop($nick,$chan))
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
	
	function hook_join($u)
	{
		$cs = Client::find("ChanServ");
		$nick = new User($u['nick']);
		$chan = new Channel($u['chan']);
		
		if (!isset($nick->account))
			return;
		
		if (!$nick->IsWordPressUser) /* user is not registered. shouldn't happen on a proper setup. */
			return;
		
		$wpuser = $nick->wp;
	
		if (cs_voice::is_autoop($wpuser,$chan->chan) == "on" && !$chan->IsOp($nick->nick))
		{
			$cs->up($chan,$nick);
		}
	}
	
	function hook_auth($u)
	{
		$cs = Client::find("ChanServ");
		
		$nick = new User($u['nick']);
		if (!($list = get_ison($nick->uid)))
			return;
		$user = new WPUser($nick->account);
		foreach ($list['list'] as $chan)
			$chan = new Channel($chan);
			if (cs_voice::is_autoop($user,$chan->chan) == "on" && !$chan->IsOp($nick->nick))
				$cs->mode($chan->chan,"+o $nick->nick");
	}
}
