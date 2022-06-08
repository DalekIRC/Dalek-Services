<?php
/*				
//	(C) 2022 DalekIRC Services
\\				
//			dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: Admin
//	
\\	Desc: Set yourself or someone else as admin in a channel
//	
\\	
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class cs_admin {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "cs_admin";
	public $description = "ChanServ OWNER and DEOWNER Commands";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
	
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{

	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		$help_string = "Set owner status on yourself or someone else in a channel";
		$syntax = "OWNER <#channel> [<nick>]";
		$extended_help = 	"$help_string\nMust have appropriate channel permissions.\n$syntax";

		if (!AddServCmd(
			'cs_admin', /* Module name */
			'ChanServ', /* Client name */
			'ADMIN', /* Command */
			'cs_admin::cmd_admin', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		$help_string = "Unset owner status on yourself or someone else in a channel";
		$syntax = "DEOWNER <#channel> [<nick>]";
		$extended_help = 	"$help_string\nMust have appropriate channel permissions.\n$syntax";

		if (!AddServCmd(
			'cs_admin', /* Module name */
			'ChanServ', /* Client name */
			'DEADMIN', /* Command */
			'cs_admin::cmd_deadmin', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_admin($u)
	{
		$cs = $u['target'];
		$parv = explode(" ",$u['msg']);
		$nick = $u['nick'];

		if (!IsLoggedIn($nick))
		{
			$cs->notice($nick->uid,"You need to login to use that command.");
			return;
		}
		
		$chan = (isset($parv[1])) ? new Channel($parv[1]) : false;
		if (!$chan)
			$chan = isset($u['mtags'][CHAN_CONTEXT]) ? new Channel($u['mtags'][CHAN_CONTEXT]) : false;
		$target = (isset($parv[2])) ? new User($parv[2]) : $nick;

		if (!$chan)
		{
			$cs->notice($nick->uid,"Syntax: /msg $cs->nick ADMIN <chan> [<nick>]");
			return;
		}

		if ($chan->UserHasMode($target->uid,"a"))
		{
			$targ = (!strcmp($target->nick,$nick->nick)) ? "You are" : "$target->nick is";
			$cs->notice_with_mtags([CHAN_CONTEXT => $chan->chan ], $nick->uid,"$targ already set as admin on that channel.");
			return;
		}

		if (ValidatePermissionsForPath("admin", $nick, $target, $chan, NULL))
			$cs->mode($chan->chan,"+a $target->nick");

		else
			$cs->notice_with_mtags([CHAN_CONTEXT => $chan->chan], $nick->uid, "Access denied!");
		return;
	}
	public static function cmd_deadmin($u)
	{
		$cs = $u['target'];
		$parv = explode(" ",$u['msg']);
		$nick = $u['nick'];

		if (!IsLoggedIn($nick))
		{
			$cs->notice($nick->uid,"You need to login to use that command.");
			return;
		}
		
		$chan = (isset($parv[1])) ? new Channel($parv[1]) : false;
		if (!$chan)
			$chan = isset($u['mtags'][CHAN_CONTEXT]) ? new Channel($u['mtags'][CHAN_CONTEXT]) : false;
		$target = (isset($parv[2])) ? new User($parv[2]) : $nick;
		if (!$chan)
		{
			$cs->notice($nick->uid,"Syntax: /msg $cs->nick DEADMIN <chan> [<nick>]");
			return;
		}

		if (!$chan->UserHasMode($target->uid,"a"))
		{
			$targ = (!strcmp($target->nick,$nick->nick)) ? "You are" : "$target->nick is";
			$cs->notice_with_mtags([CHAN_CONTEXT => $chan->chan ], $nick->uid,"$targ not admin on that channel.");
			return;
		}

		if (ValidatePermissionsForPath("admin", $nick, $target, $chan, NULL))
			$cs->mode($chan->chan,"-a $target->nick");

		else
			$cs->notice_with_mtags([CHAN_CONTEXT => $chan->chan], $nick->uid, "Access denied!");
		return;
	}

}

