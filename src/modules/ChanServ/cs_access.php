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

		if (!BadPtr($parv[4]))
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

		if (!($acount = new WPUser($parv[3]))->IsUser) // couldn't find the account. search for a nick with an account
		{
			if (!($account = new User($parv[3]))->IsUser)
			{
				sendnotice($nick, $cs, $mtags, "Could not find anyone by that nick or account.");
				return;
			}
		}
		$account = new WPUser($account->account);


		

	}

	function perform_checkup()
	{
		$conn = sqlnew();
		if (!$conn)
			return;
		
		$result = $conn->query("SELECT * FROM ".sqlprefix()."chaninfo");
		if (!$result)
			return;
		if ($result->num_rows == 0)
			return;
		
		while ($row = $result->fetch_assoc())
		{
			$chan = new Channel($row['channel']);
			if (!$chan->IsChan)
				continue;

			if (!$chan->HasUser("ChanServ"))
				Client::find("ChanServ")->join($chan->chan);
			if (!$chan->HasMode("r") == false)
				$chan->SetMode("+r");
		}
	}
	function ACCESS_channel($chan,$owner)
	{
		$servertime = servertime();
		$conn = sqlnew();
		if (!$conn)
			return false;
		$prep = $conn->prepare("INSERT INTO ".sqlprefix()."chaninfo (channel, owner, regdate) VALUES (?, ?, ?)");
		$prep->bind_param("sss",$chan,$owner,$servertime);
		$prep->execute();
		
		$permission = "owner";
		$prep = $conn->prepare("INSERT INTO ".sqlprefix()."chanaccess (channel, nick, access) VALUES (?, ?, ?)");
		$prep->bind_param("sss",$chan,$owner,$permission);
		$prep->execute();
		return true;
	}

	function init_db()
	{
		$conn = sqlnew();
	
		$query = "CREATE TABLE IF NOT EXISTS ".sqlprefix()."chaninfo (
					id int AUTO_INCREMENT NOT NULL,
					channel varchar(255) NOT NULL,
					owner varchar(255) NOT NULL,
					regdate varchar(15) NOT NULL,
					url varchar(255),
					email varchar(255),
					topic varchar(255),
					PRIMARY KEY(id)
				)";
		$conn->query($query);
		
		$query = "CREATE TABLE IF NOT EXISTS ".sqlprefix()."chanaccess (
					id int AUTO_INCREMENT NOT NULL,
					channel varchar(255) NOT NULL,
					nick varchar(255) NOT NULL,
					access varchar(20) NOT NULL,
					PRIMARY KEY(id)
				)";
		$conn->query($query);
	}

	function hook_do_join($u)
	{
		$chan = new Channel($u['chan']);
		$cs = Client::find("ChanServ");
		if ($chan->IsReg)
		{
			if (!$chan->HasUser($cs->nick))
				$cs->join($chan->chan);
				
			if (!$chan->HasMode("r"))
				$cs->mode($chan->chan,"+r");
			
		}
	}
}
