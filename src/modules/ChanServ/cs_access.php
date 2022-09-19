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
\\	Title: ACCESS
//	
\\	Desc: Lets users change access settings on their channel
\\	
//	
\\	Version: 1.2
//				
\\	Author:	Valware
//				
*/
class cs_access {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "cs_access";
	public $description = "ChanServ ACCESS Command";
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
	 * after the module has been successfully ACCESSed.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be ACCESSed first
	*/
	function __init()
	{
		$help_string = "ACCESS a channel to your account";
		$syntax = "ACCESS <#channel>";
		$extended_help = 	"$help_string\n$syntax";

		if (!AddServCmd(
			'cs_access', /* Module name */
			'ChanServ', /* Client name */
			'ACCESS', /* Command */
			'cs_access::cmd_access', /* Command function */
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
	public static function cmd_access($u)
	{
		$cs = $u['target'];
		$nick = $u['nick'];
		$parv = split($u['msg']);

		if (!IsLoggedIn($nick))
		{
			$cs->notice($nick->uid,"You must be logged in to use that command.");
			return;
		}

		if (BadPtr($parv[2]))
		{
			$cs->notice($nick->uid,"Syntax: /msg $cs->nick ACCESS <channel> <add|del|list> [<account|nick>]");
			return;
		}

		/* find channel lol */
		$chan = new Channel($parv[1]);

		/* we are going to be using channel-context as an mtag here
		 * but only show it if they are on that channel lol
		*/
		$mtags = ($chan->HasUser($nick->nick)) ? [ "+draft/channel-context" => $chan->chan ] : NULL;

		if (!$chan->IsReg)
		{
			sendnotice($nick, $cs, $mtags, "That channel is not registered.");
			return;
		}

		if (strcasecmp($chan->owner,$nick->account) && ChanAccessAsInt($chan, $nick) < 5 && !IsOper($nick))
		{
			sendnotice($nick, $cs, $mtags,"Permission denied!");
			return;
		}

		$control = $parv[2];
		$lvl = (isset($parv[4])) ? $parv[4] : NULL;
		if (strcasecmp($control,"list") && !($account = new WPUser($parv[3]))->IsUser) // couldn't find the account. search for a nick with an account
		{
			if (!($account = new User($parv[3]))->IsUser)
			{
				sendnotice($nick, $cs, $mtags, "Could not find anyone by that nick or account.");
				return;
			}
		}
		if (strcasecmp($control,"list"))
			$account = ($account instanceof WPUser) ? $account : $account->wp;

		if (strcasecmp($control,"add") && strcasecmp($control,"del") && strcasecmp($control,"list"))
		{
			sendnotice($nick, $cs, $mtags, "Invalid subcommand \"$control\"");
			return;
		}

		if (!strcasecmp($control,"add") && strcasecmp($lvl,"owner") && strcasecmp($lvl,"admin") && strcasecmp($lvl,"op") && strcasecmp($lvl,"halfop") && strcasecmp($lvl,"voice"))
		{
			sendnotice($nick, $cs, $mtags, "Invalid access level: \"$lvl\"");
			return;
		}

		if (!strcasecmp($control,"add"))
		{
			self::add_access($chan, $account->user_login, $lvl);
			sendnotice($nick, $cs, $mtags, "You have added permissions of $lvl to $account->user_login in $chan->chan");
			foreach ($chan->userlist as $user)
				if (isset($user->account) && !strcasecmp($user->account,$account->user_login))
					$cs->up($chan, $user);
		}
		elseif (!strcasecmp($control,"del"))
		{
			foreach ($chan->userlist as $user)
				if (isset($user->account) && !strcasecmp($user->account,$account->user_login))
					$cs->down($chan,$user);
			self::del_access($chan, $account->user_login);

			sendnotice($nick, $cs, $mtags, "You have removed permissions of $account->user_login in $chan->chan");
		}
		elseif (!strcasecmp($control,"list"))
		{
			$list = self::access_list($chan);
			if (empty($list))
			{
				sendnotice($nick, $cs, $mtags, "No access list. Weird. So how can you see this message? Hmm... ;)");
				return;
			}
			
			sendnotice($nick, $cs, $mtags, "Listing access list for channel $chan->chan:");
			foreach($list as $acc)
			{
				sendnotice($nick, $cs, $mtags, clean_align(bold("Nick: ").$acc["nick"])." ".bold("Access: ").$acc["access"]);
			}

		}
	}

	public static function add_access(Channel $chan, $account_name, $level)
	{
		$chan = strtolower($chan->chan);
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."chanaccess WHERE nick = ? and lower(channel) = ?");
		$prep->bind_param("ss", $account_name, $chan);
		$prep->execute();

		if (!($result = $prep->get_result()) || !$result->num_rows)
		{
			$prep = $conn->prepare("INSERT INTO ".sqlprefix()."chanaccess (channel, nick, access) VALUES (?, ?, ?)");
			$prep->bind_param("sss", $chan, $account_name, $level);
			$prep->execute();
		}

		else
		{
			$prep = $conn->prepare("UPDATE ".sqlprefix()."chanaccess SET access = ? WHERE lower(channel) = ? AND nick = ?");
			$prep->bind_param("sss",$level, $chan, $account_name);
			$prep->execute();
		}
		$prep->close();
	}
	public static function del_access(Channel $chan, $account_name)
	{
		$chan = strtolower($chan->chan);
		$conn = sqlnew();
		$prep = $conn->prepare("DELETE FROM ".sqlprefix()."chanaccess WHERE nick = ? AND lower(channel) = ?");
		$prep->bind_param("ss", $account_name, $chan);
		$prep->execute();

		$prep->close();
	}

	public static function access_list(Channel $chan)
	{
		$return = [];
		$conn = sqlnew();
		$chan = strtolower($chan->chan);
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."chanaccess WHERE lower(channel) = ?");
		$prep->bind_param("s", $chan);
		$prep->execute();
		$result = $prep->get_result();
		if (!$result || !$result->num_rows)
			return $return;

		while($row = $result->fetch_assoc())
			$return[] = $row;

		return $return;
	}
}
