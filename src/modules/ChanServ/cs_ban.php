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
\\	Title: NickServ BAN command
//	
\\	Desc:	Allows chan_ops
//
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/


/* Our class! This is the module itself. It needs to be named the same as the file, without ".php" */
class cs_ban {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "cs_ban";
	public $description = "NickServ normal BAN";
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
		$help_string = "Ban someone from a channel";
		$syntax = "BAN <#channel> [<flags>] <nick!user@host> [...<nick!user@host>]";
		$extended_help = 	"$help_string\Takes multiple targets. If you specify a nick without\n".
							"a mask, both the nick and the hostmask will be banned. If the user is using an\n".
							"IRC Cloud connection, their ident will be banned instead of their hostmask.\n \n".
							"Must have appropriate channel permissions.\n \n$syntax\n".
							"Flags:\n".
							"-qnjascN\n \n".
							"-q Quiet - People matching these bans can join but are unable to speak, unless they have +v or higher.\n".
							"-n NickChange - People matching these bans cannot change nicks, unless they have +v or higher.\n".
							"-j Join - Users matching this may not join the channel, but may still speak if they already are on it.\n".
							"-a Account - Matches if a user is logged in to services with this account name.\n".
							"-s CertFP - Matches if a user is using this Certificate FingerPrint\n".
							"-c CountryCode - Matches if a user is marked as connecting from this country code (eg \"GB\")\n".
							"-N - Timed ban, where N represents and should be replaced with a number of minutes. Example \"-30\"\n \n".
							"Example for a timed quiet ban lasting 30 minutes:\n".
							"BAN #PossumsOnly -q30 Lamer_20";

		if (!AddServCmd(
			'cs_ban', /* Module name */
			'ChanServ', /* Client name */
			'BAN', /* Command */
			'cs_ban::cmd_ban', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		$help_string = "Unban someone from a channel";
		$syntax = "UNBAN <#channel> [<nick!user@host>] [...<nick!user@host>]";
		$extended_help = 	"$help_string\nMust match an existing ban and have appropriate channel permissions.\n$syntax";

		if (!AddServCmd(
			'cs_ban', /* Module name */
			'ChanServ', /* Client name */
			'UNBAN', /* Command */
			'cs_ban::cmd_unban', /* Command function */
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
	public static function cmd_ban($u)
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
		
		$mtags = ($chan) ? [CHAN_CONTEXT => $chan->chan] : NULL;

		if (!$chan->IsChan || !isset($parv[2]) || (isset($parv[2]) && $parv[2][0] == "-" && !isset($parv[3]))) // Error checking
		{
			$cs->notice_with_mtags($mtags,$nick->uid,"Syntax: /msg $cs->nick BAN <chan> [flags] [<nick!user@host>] [...<nick!user@host>]");
			return;
		}

		/* Do we have flags? */
		$flags = NULL;
		if ($parv[2][0] == "-")
		{
			$flags = mb_substr($parv[2],1);

			/* drop the params back one to account for our optional param */
			for ($i = 2; isset($parv[$i]); $i++)
				$parv[$i] = (isset($parv[$i + 1])) ? $parv[$i + 1] : NULL;
		}

		/* list our target(s) */
		$targets = [];
		for ($i = 2; isset($parv[$i]); $i++)
			$targets[] = $parv[$i];

		$newflags = "";
		$dupflags = $flags;
		
		$bigstring = ""; // the big money
		while(strlen($dupflags)) // Convert them
		{
			$c = $dupflags[0];

			if ($c == "q")
				$newflags .= "~quiet:";
			elseif ($c == "n")
				$newflags .= "~nickchange:";
			elseif ($c == "j")
				$newflags .= "~join:";
			elseif ($c == "a")
				$newflags .= "~account:";
			elseif ($c == "s")
				$newflags .= "~certfp:";
			elseif ($c == "c")
				$newflags .= "~country:";
			elseif (is_numeric($dupflags) && !strpos($dupflags,"."))
			{
				$newflags = "~time:$dupflags:".$newflags;
				break;
			}
			else // uh oh! something we didn't recognise. syntax error I guess
			{
				$cs->notice_with_mtags
				($mtags,$nick->uid,
					"Syntax: /msg $cs->nick BAN <chan> [flags] [<nick!user@host>] [...<nick!user@host>]",
					"For more information on flags, type /msg $cs->nick HELP BAN"
				);
				return;
			}
			$dupflags = mb_substr($dupflags,1);
		}
		
		$i = 0;

		$toKick = [];
		$targmask = NULL;
		/* Validate the user can set bans on the targets */
		foreach($targets as $targ)
		{
			if (strpos($flags,"a") || strpos($flags,"c") || strpos($flags,"s")) /* if it's an account, certfp or country, assume it's not a nick */
			{
				$targmask = $targ;
			}
			elseif(strpos($targ,"!") || strpos($targ,"@") || strpos($targ,"*")) /* it's a hostmask, don't look it up */
			{
				$targmask = $targ;
			}
			elseif(($victim = new User($targ))->IsUser)
			{
				if ($victim == $nick)
				{
					$cs->notice_with_mtags($mtags,$nick->uid,"Woops! Looks like you tried to ban yourself.");
					continue;
				}

				if (!ValidatePermissionsForPath("can_ban",$nick,$victim,$chan))
				{
					$cs->notice_with_mtags($mtags,$nick->uid,"Could not ban $targ - Permission denied!");
					continue;
				}

				if (substr($victim->realhost,-12) == "irccloud.com") // Might have to ban differently
				{
					if (substr($victim->realhost,0,3) == "id-")	// nope
						$targmask = "*!*@$victim->cloak";
					else 										// yep
						$targmask = "*!$victim->ident@*";
				}
				else $targmask = "*!*@$victim->cloak";
				if (!strstr($flags,"q") && !strstr($flags,"j") && !strstr($flags,"n"))
					$toKick[] = $victim;
			}
			else $targmask = "$targ!*@*";
			if (is_a_ban($chan,$targmask))
			{
				$cs->notice($nick->uid,"$targ is already banned on $chan->chan");
				continue;
			}
			/* ASSEMBLE */
			$bigstring .= "$newflags$targmask ";
			$i++;
		}
		$bb = "+";
		for ($b=0;$b<=$i;$b++)
			$bb .= "b";

		$cs->mode($chan->chan,"$bb $bigstring");
		foreach($toKick as $kick)
			$cs->kick($chan->chan,$kick->nick,"You have been banned ($nick->nick)");	
		
	}
	public static function cmd_unban($u)
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
		
		$mtags = ($chan) ? [CHAN_CONTEXT => $chan->chan] : NULL;

		if (!$chan->IsChan || !isset($parv[2]) || (isset($parv[2]) && $parv[2][0] == "-" && !isset($parv[3]))) // Error checking
		{
			$cs->notice_with_mtags($mtags,$nick->uid,"Syntax: /msg $cs->nick UNBAN <chan> [flags] [<nick!user@host>] [...<nick!user@host>]");
			return;
		}

		if (!ValidatePermissionsForPath("can_unban",$nick,NULL,$chan))
		{
			$cs->notice_with_mtags($mtags,$nick->uid,"Permission denied!");
			return;
		}
		/* list our target(s) */
		$targets = [];
		
		for ($i = 2; isset($parv[$i]); $i++)
			$targets[] = $parv[$i];

		$bb = "-";
		$bigstring = "";
		foreach($targets as $targ)
		{
			if (!is_a_ban($chan,$targ))
			{
				$cs->notice($nick->uid,"$targ is not banned on that channel");
				continue;
			}
			$bigstring .= "$targ ";
			$bb .= "b";
		}
		$cs->mode($chan->chan,"$bb $bigstring");
	}

}
