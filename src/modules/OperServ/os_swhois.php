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
\\	Title: OperServ SWHOIS
//	
\\	Desc: View and modify a users Special WHOIS lines
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class os_swhois {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "os_swhois";
	public $description = "View and modify a users Special WHOIS lines";
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
		/* We automatically clear up things attached to the module information, like AddServCmd();
		 * so don't worry!
		*/
	}


	function __init()
	{
		$cmd = "SWHOIS";
		$help_string = "View and modify a users Special WHOIS lines";
		$syntax = "$cmd <user> <list|add|del> <tag|*> [<swhois line>]";
		$extended_help = 	"$help_string\n$syntax.";

		if (!AddServCmd(
			'os_swhois', /* Module name */
			'OperServ', /* Client name */
			$cmd, /* Command */
			'os_swhois::cmd', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		return true;
	}
	
	function cmd($u)
	{
		$parv = explode(" ",$u['msg']);
		$os = $u['target'];
		$nick = $u['nick'];

		if (!ValidatePermissionsForPath("can_swhois", $nick))
		{
			$os->notice($nick->uid,"Permission denied!");
			return;
		}

		if (!isset($parv[3]))
		{
			$os->notice($nick->uid,"Invalid parameters.","Syntax: /msg $os->nick SWHOIS <user> <list|add|del> <tag|*> [<swhois line>]");
			return;
		}
		if (!($target = new User($parv[1]))->IsUser)
		{
			$os->notice($nick->uid,"Could not find user: ".$parv[1]);
			return;
		}

		if (!strcasecmp($parv[2],"list"))
		{
			$list = specialwhois::list_swhois_for_user($target);
			if (empty($list))
			{
				$os->notice($nick->uid,"$target->nick does not have any Special WHOIS lines.");
				return;
			}
			$os->notice($nick->uid,"Listing Special WHOIS lines for $target->nick:");
			$i = 1;
			$os->notice($nick->uid,clean_align(ul("Tag")).ul("Line"));
			foreach($list as $tag => $line)
				$os->notice($nick->uid,clean_align($tag).$line);
			
		}
		elseif (!strcasecmp($parv[2],"add"))
		{
			if (!isset($parv[4]))
			{
				$os->notice($nick->uid,"Invalid parameters.","Syntax: /msg $os->nick SWHOIS <user> <list|add|del> <tag|*> [<swhois line>]");
				return;
			}
			$swhois = $parv[4]." ";
			for ($i = 5; isset($parv[$i]); $i++)
				$swhois .= $parv[$i]." ";

			$swhois = trim($swhois);
			if (!strlen($swhois))
			{
				$os->notice($nick->uid,"Invalid parameters.","Syntax: /msg $os->nick SWHOIS <user> <list|add|del> <tag|*> [<swhois line>]");
				return;
			}
			SVSLog("$nick->nick ($nick->ident@$nick->realhost) used SWHOIS to add a new line with tag '".$parv[3]."' from $target->nick's WHOIS: $swhois");
			$os->notice($nick->uid,"Line successfully added to $target->nick's /WHOIS");
			specialwhois::send_swhois($target->nick,$parv[3],$swhois);
		}
		elseif (!strcasecmp($parv[2],"del"))
		{
			if (!isset($parv[3]))
			{
				$os->notice($nick->uid,"Invalid parameters.","Syntax: /msg $os->nick SWHOIS <user> <list|add|del> <tag|*> [<swhois line>]");
				return;
			}
			if (!specialwhois::is_swhois($target->nick,$parv[3]))
			{
				$os->notice($nick->uid,"That Special WHOIS tag does not exist for that user.");
				return;
			}
			SVSLog("$nick->nick ($nick->ident@$nick->realhost) used SWHOIS to delete line with tag '".$parv[3]."' from $target->nick's WHOIS");
			$os->notice($nick->uid,"Line successfully removed from $target->nick's /WHOIS");
			specialwhois::del_swhois($target->nick,$parv[3]);
		}
	}
}