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
\\	Title: Templ8 4 Services command!
//	
\\	Desc:	This template is designed to show you how to add a command to
//			a services bot (NickServ, ChanServ, whatever). The bot must
\\			actually be loaded if you want to load a command for it.
//
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/

require_module("sjoin");

/* Our class! This is the module itself. It needs to be named the same as the file, without ".php" */
class cs_autoop {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "cs_autoop";
	public $description = "Auto OP";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		/* Lets define our command =] */
		define("CMD_CS_AUTOOP","AUTOOP");

	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		hook::del("join", 'cs_autoop::hook_join');
		hook::del("auth", 'cs_autoop::hook_auth');
	}

	/* This part is the _inititalisation! This is ran when the module has been successfully loaded */
	function __init()
	{
		/* Lets add our command to ChanServ =]
		 * This is where we put our help string, syntax, and extended help for the 'HELP' command output.
		 * Just for a kind of 'all-in-one' thing
		 */
		$help_string = "Modify your AUTOOP setting for a channel";
		$syntax = "AUTOOP <channel> <on|off>";
		$extended_help = "Allows you to enable/disable being automatically set +o in a channel you have op permissions in.";

		if (!AddServCmd(
			'cs_autoop', /* Module name */
			'ChanServ', /* Client name */
			CMD_CS_AUTOOP, /* Command */
			'cs_autoop::function', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false; /* If something went wrong, we gotta back out and unload the module */

		hook::func("join", 'cs_autoop::hook_join');
		hook::func("auth", 'cs_autoop::hook_auth');
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
		$mtags = ($chan->HasUser($nick->nick)) ? [CHAN_CONTEXT => $chan->chan] : NULL;
		if (cs_autoop::can_autoop($account,$chan))
		{
			$user = $nick->wp;
			
			if ($toggle == "on" && cs_autoop::is_autoop($user,$chan->chan) == "on")
			{
				sendnotice($nick, $cs, $mtags, "AUTOOP is already set to 'on' for $chan->chan");
				return;
			}
			
			elseif ($toggle == "off" && (!$isop = cs_autoop::is_autoop($user,$chan->chan) || $isop = "off"))
			{
				sendnotice($nick, $cs, $mtags, "AUTOOP is already set to 'off' for $chan->chan");
				return;
			}
			
			cs_autoop::autoop_toggle($user,$chan->chan,$toggle);
			sendnotice($nick, $cs, $mtags, "AUTOOP has been set to '$toggle' for $chan->chan");
			return;
		}
		sendnotice($nick, $cs, $mtags, "Permission denied.");
	}

	function can_autoop($nick,Channel $channel)
	{
		$access = ChanAccess($channel,$nick);
		if ($access == "owner" || $access == "operator" || $access == "admin")
			return true;
		return false;
	}

	static function is_autoop(WPUser $nick,$chan)
	{
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."account_settings WHERE account = ?");
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
		if (!cs_autoop::is_autoop($nick,$chan))
		{
			$prep = $conn->prepare("INSERT INTO ".sqlprefix()."account_settings (account, setting_key, setting_value) VALUES (?, ?, ?)");
			$prep->bind_param("sss",$nick->user_login,$setting_key,$toggle);
			$prep->execute();
		}
		else
		{
			$prep = $conn->prepare("UPDATE ".sqlprefix()."account_settings SET setting_key = ?, setting_value = ? WHERE account = ?");
			$prep->bind_param("sss",$setting_key,$toggle,$nick->user_login);
			$prep->execute();
		}
	}
	
	public static function hook_join($u)
	{
		$cs = Client::find("ChanServ");
		$nick = new User($u['nick']);
		$chan = new Channel($u['chan']);
		
		if (!isset($nick->account))
			return;
		
		if (!$nick->IsWordPressUser) /* user is not registered. shouldn't happen on a proper setup. */
			return;
		
		$wpuser = $nick->wp;
	
		if (cs_autoop::is_autoop($wpuser,$chan->chan) == "on" && !$chan->IsOp($nick))
		{
			$cs->up($chan,$nick);
		}
	}
	
	public static function hook_auth($u)
	{
		$cs = Client::find("ChanServ");
		
		$nick = new User($u['nick']);
		if (!($list = get_ison($nick->uid)))
			return;
		$user = new WPUser($nick->account);
		foreach ($list['list'] as $chan)
		{
			$chan = new Channel($chan);
			if (cs_autoop::is_autoop($user,$chan->chan) == "on" && !$chan->IsOp($nick))
				$cs->mode($chan->chan,"+o $nick->nick");
		}
	}
}
