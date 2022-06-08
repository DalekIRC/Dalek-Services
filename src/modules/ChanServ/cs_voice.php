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


/* Our class! This is the module itself. It needs to be named the same as the file, without ".php" */
class cs_voice {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "cs_voice";
	public $description = "NickServ command for VOICE";
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

	/* This part is the _inititalisation! This is ran when the module has been successfully loaded */
	function __init()
	{
		$help_string = "Voice yourself or someone else in a channel";
		$syntax = "VOICE <#channel> [<nick>]";
		$extended_help = 	"$help_string\nMust have appropriate channel permissions.\n$syntax";

		if (!AddServCmd(
			'cs_voice', /* Module name */
			'ChanServ', /* Client name */
			'VOICE', /* Command */
			'cs_voice::cmd_voice', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		$help_string = "DeVoice yourself or someone else in a channel";
		$syntax = "DEVOICE <#channel> [<nick>]";
		$extended_help = 	"$help_string\nMust have appropriate channel permissions.\n$syntax";

		if (!AddServCmd(
			'cs_voice', /* Module name */
			'ChanServ', /* Client name */
			'DEVOICE', /* Command */
			'cs_voice::cmd_devoice', /* Command function */
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
	public static function cmd_voice($u)
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
			$cs->notice($nick->uid,"Syntax: /msg $cs->nick VOICE <chan> [<nick>]");
			return;
		}

		if ($chan->IsVoice($target->uid) !== false)
		{
			$targ = (!strcmp($target->nick,$nick->nick)) ? "You are" : "$target->nick is";
			$cs->notice_with_mtags([CHAN_CONTEXT => $chan->chan ], $nick->uid,"$targ already voiced on that channel.");
			return;
		}

		if (ValidatePermissionsForPath("voice", $nick, $target, $chan, NULL))
			$cs->mode($chan->chan,"+v $target->nick");

		else
			$cs->notice_with_mtags([CHAN_CONTEXT => $chan->chan], $nick->uid, "Access denied!");
		return;
	}
	public static function cmd_devoice($u)
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
			$cs->notice($nick->uid,"Syntax: /msg $cs->nick DEVOICE <chan> [<nick>]");
			return;
		}

		if (!$chan->IsVoice($target->uid))
		{
			$targ = (!strcmp($target->nick,$nick->nick)) ? "You are" : "$target->nick is";
			$cs->notice_with_mtags([CHAN_CONTEXT => $chan->chan ], $nick->uid,"$targ are not voiced on that channel.");
			return;
		}

		if (ValidatePermissionsForPath("voice", $nick, $target, $chan, NULL))
			$cs->mode($chan->chan,"-v $target->nick");

		else
			$cs->notice_with_mtags([CHAN_CONTEXT => $chan->chan], $nick->uid, "Access denied!");
		return;
	}

}
