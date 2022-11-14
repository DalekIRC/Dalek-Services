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
\\	Title: NickServ's VERSION SERVERS
//	
\\	Desc:	Gets the versions of servers
//
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/


/* Our class! This is the module itself. It needs to be named the same as the file, without ".php" */
class ns_version_servers {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ns_version_servers";
	public $description = "Versioning servers since 2022 lmao";
	public $author = "Valware";
	public $version = "1.0";

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		$conn = sqlnew();
		$conn->query(
			"CREATE TABLE IF NOT EXISTS dalek_server_version (
				id int AUTO_INCREMENT NOT NULL,
				sid varchar(5) NOT NULL,
				version varchar(255) NOT NULL,
				PRIMARY KEY(id)
			)");
		$conn->query("TRUNCATE TABLE dalek_server_version");
		$prep = $conn->prepare("INSERT INTO dalek_server_version (sid, version) VALUES (?, ?)");
		$sid = Conf::$settings['info']['SID'];
		$version = DALEK_VERSION;
		$prep->bind_param("ss", $sid, $version);
		$prep->execute();
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		/* We automatically clear up things attached to the module information, like AddServCmd();
		 * so don't worry!
		*/
	}

	/* This part is the _inititalisation! This is ran when the module has been successfully loaded */
	function __init()
	{
		/* Lets add our command to NickServ =]
		 * This is where we put our help string, syntax, and extended help for the 'HELP' command output.
		 * Just for a kind of 'all-in-one' thing
		 */
		hook::func(HOOKTYPE_START, 'ns_version_servers::run_immediately');
		hook::func(HOOKTYPE_RAW, 'ns_version_servers::raw');
		if (IsConnected())
			ns_version_servers::run_immediately();
		return true; /* weeee are good */
	}
	
	public static function run_immediately()
	{
		global $nickserv;
		$servers = [];
		$conn = sqlnew();
		if (!($ns = Client::find($nickserv['nick']))) // where is our bot?!?!
			return;
		if (!($result = $conn->query("SELECT * FROM ".sqlprefix()."server")))
			return;
		while ($row = $result->fetch_assoc())
		{
			if ($row['sid'] == Conf::$settings['info']['SID'])
				continue;
			S2S(":$ns->nick VERSION ".$row['servername']);
		}
	}

	public static function raw($u)
	{
		$parv = split($u['string']);
		$serv = [];
		/* one of those commands without a 'sender', spoof it as our uplink */
		if ($parv[0][0] !== ":")
		{
			if (!($serv = serv_attach(Conf::$settings['info']['SID'])))
				$serv = [Conf::$settings['info']['SID']];
			$u['string'] = ":".$serv[0]." ".$u['string'];
			$parv = split($u['string']);
		}

		$u['string'] = mb_substr($u['string'], 1);
		$parv = split($u['string']);
		$serv = new User($parv[0]);
		
		if ($parv[1] != "351")
			return;
		
		self::update_version($serv->uid, $parv[3]);
	}

	public static function update_version($sid, $version)
	{
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT * FROM dalek_server_version WHERE sid = ?");
		$prep->bind_param("s", $sid);
		$prep->execute();
		if (!$prep->get_result())
		{
			$prep = $conn->prepare("INSERT INTO dalek_server_version (sid, version) VALUES (?, ?)");
			$prep->bind_param("ss", $sid, $version);
			$prep->execute();
		}
		else
		{
			$prep = $conn->prepare("UPDATE dalek_server_version SET version = ? WHERE sid = ?");
			$prep->bind_param("ss", $version, $sid);
			$prep->execute();
		}
	}
}
