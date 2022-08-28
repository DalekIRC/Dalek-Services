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
\\	Title: Register
//	
\\	Desc: Register a channel
//	
\\	
//	
\\	Version: 1.2
//				
\\	Author:	Valware
//				
*/
class cs_register {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "cs_register";
	public $description = "ChanServ REGISTER Command";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		cs_register::init_db();
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		hook::del(HOOKTYPE_JOIN, 'cs_register::hook_do_join');
		hook::del(HOOKTYOE_START, 'cs_register::perform_checkup');
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		$help_string = "Register a channel to your account";
		$syntax = "REGISTER <#channel>";
		$extended_help = 	"$help_string\n$syntax";

		if (!AddServCmd(
			'cs_register', /* Module name */
			'ChanServ', /* Client name */
			'REGISTER', /* Command */
			'cs_register::cmd_register', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		hook::func(HOOKTYPE_JOIN, 'cs_register::hook_do_join');
		hook::func(HOOKTYPE_START, 'cs_register::perform_checkup');
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_register($u)
	{
		$cs = $u['target'];
		$nick = $u['nick'];
		$parv = explode(" ",$u['msg']);

		if (!IsLoggedIn($nick))
		{
			$cs->notice($nick->uid,"You must be logged in to use that command.");
			return;
		}

		if (!isset($parv[1]))
		{
			$cs->notice($nick->uid,"Syntax: /msg $cs->nick REGISTER <channel>");
			return;
		}

		/* find channel lol */
		$chan = new Channel($parv[1]);

		/* we are going to be using channel-context as an mtag here */
		$mtags = [ "+draft/channel-context" => $chan->chan ];

		if ($chan->IsReg)
		{
			$cs->notice_with_mtags($mtags, $nick->uid,"That channel is already registered.");
			return;
		}
		
		if (!$chan->HasUser($nick->uid))
		{
			$cs->notice_with_mtags($mtags, $nick->uid,"You must be on that channel to register it.");
			return;
		}
		if (!$chan->IsOp($nick->uid))
		{
			$cs->notice_with_mtags($mtags, $nick->uid,"You must be an operator on that channel to register it.");
			return;
		}
		

		if (!($reg = cs_register::register_channel($chan->chan,$nick->account)))
		{
			$cs->notice_with_mtags($mtags, $nick->uid,"Could not register channel at this time.");
			return;
		}
		$chan->SetMode("+rq $nick->nick");
		$cs->join($chan->chan);
		$cs->log("Channel $chan->chan has been registered by $nick->nick to account $nick->account");
		$cs->notice($nick->uid,"Channel $chan->chan has been registered under account $nick->account");
		
	}

	function perform_checkup()
	{
		$conn = sqlnew();
		if (!$conn)
			return;
		
		$result = $conn->query("SELECT * FROM dalek_chaninfo");
		if (!$result)
			return;
		if ($result->num_rows == 0)
			return;
		
		while ($row = $result->fetch_assoc())
		{
			$chan = new Channel($row['channel']);
			if (!$chan->IsChan)
				continue;
			cs_register::hook_do_join(array("chan" => $row['channel']));
		}
	}
	function register_channel($chan,$owner)
	{
		$servertime = servertime();
		$conn = sqlnew();
		if (!$conn)
			return false;
		$prep = $conn->prepare("INSERT INTO dalek_chaninfo (channel, owner, regdate) VALUES (?, ?, ?)");
		$prep->bind_param("sss",$chan,$owner,$servertime);
		$prep->execute();
		
		$permission = "owner";
		$prep = $conn->prepare("INSERT INTO dalek_chanaccess (channel, nick, access) VALUES (?, ?, ?)");
		$prep->bind_param("sss",$chan,$owner,$permission);
		$prep->execute();
		return true;
	}

	function init_db()
	{
		$conn = sqlnew();
	
		$query = "CREATE TABLE IF NOT EXISTS dalek_chaninfo (
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
		
		$query = "CREATE TABLE IF NOT EXISTS dalek_chanaccess (
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
