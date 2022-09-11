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
\\	Title:	MetaServ's HOST command
//	
\\	Desc:	Allows users to request virtual hosts
//
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/


/* Our class! This is the module itself. It needs to be named the same as the file, without ".php" */
class meta_host {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "meta_host";
	public $description = "A template for adding a command to NickServ";
	public $author = "Valware";
	public $version = "1.0";

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	public function __construct()
	{
		$conn = sqlnew();
		$conn->query("CREATE TABLE IF NOT EXISTS ".sqlprefix()."meta_hosts (
			id INT AUTO_INCREMENT NOT NULL,
			account VARCHAR(255) NOT NULL,
			approved INT NOT NULL,
			vhost VARCHAR(255) NOT NULL,
			auto INT NOT NULL,
			PRIMARY KEY (id)
		)");
	}

	
	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	public function __destruct()
	{
		/* We automatically clear up things attached to the module information, like AddServCmd();
		 * so don't worry!
		*/
	}

	/* This part is the _inititalisation! This is ran when the module has been successfully loaded */
	public function __init()
	{
		/* Lets add our command to NickServ =]
		 * This is where we put our help string, syntax, and extended help for the 'HELP' command output.
		 * Just for a kind of 'all-in-one' thing
		 */
		$help_string = "Manage your vHost";
		$syntax = "HOST <option> [sub option]";
		$extended_help = "Allows you to request a vhost, turn it on/off if you have one,\n".
							"enable it activating automatically when you login, and more:\n \n".
							"Syntax:\n \n".
							"HOST REQUEST <vhost>\n".
							"HOST <ON|OFF>\n".
							"HOST AUTOHOST <ON|OFF>\n \n".
							"Examples:\n \n".
							"/msg MetaServ HOST REQUEST my.awesome.vhost.lol\n".
							"/msg MetaServ HOST ON\n".
							"/msg MetaServ HOST AUTOHOST ON";

		if (!AddServCmd(
			'meta_host', /* Module name */
			'MetaServ', /* Client name */
			'HOST', /* Command */
			'meta_host::function', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false; /* If something went wrong, we gotta back out and unload the module */

		return true; /* weeee are good */
	}
	
	public function function($u)
	{
		/* Grab our target Client object (NickServ) */
		$meta = $u['target'];

		/* Grab our requester User object */
		$nick = $u['nick'];

		/* message */
		$parv = split($u['msg']);

		/* If we are requesting a vhost */
		if (!strcasecmp($parv[1],"request"))
		{

		}
	}
	
	/* Called when a new vhost is successfully requested
	 * Services opers are automatically approved
	 */
	public function new_meta_host($account, $vhost, $auto_approve = 0) : void
	{
		$conn = sqlnew();
		$prep = $conn->prepare("INSERT INTO ".sqlprefix()."meta_hosts (account, vhost, approved, auto) VALUES (?, ?, ?, ?)");
		$prep->bind_param("ssii", $account, $vhost, $auto_approve, $auto_approve);
		$prep->execute();

	}

	/* Check if an accounts vhost is approved
	 * returns bool
	 */
	public function IsApproved($account) : bool
	{
		$conn = sqlnew();
		$approved = 1;
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."meta_hosts WHERE account = ? AND approved = ?");
		$prep->bind_param("si", $account, $approved);
		$prep->execute();
		$result = $prep->get_result();
		if (!$result || !$result->num_rows)
			return false;
		return true;
	}

	
}
