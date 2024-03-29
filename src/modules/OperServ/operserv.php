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
\\	Title: OperServ
//	
\\	Desc: OperServ
//	
\\		OperServ Pseudoclient
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
include("operserv.conf");

/* Our module filename needs to be the same as the class and must be unique
 * This allows for easy modularity, for loading and unloading your module.
 */
class operserv {

	/* Module handle */
	public $name = "operserv"; 	/* $name needs to be the same name as the class and file lol */
	public $description = "OperServ PseudoClient"; /* $description of what the module does. This will show in the MODULE response to the server. */
	public $author = "Valware"; /* Who you are */
	public $version = "1.0"; /* The version of the module. You should increment this if you change it. */
	public $official = true; /* This marks the module as official. You should not specify this if you are writing a module for Dalek-Contrib */

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		/* Creating out tables for account settings which apply to IRC. */
		$query = "CREATE TABLE IF NOT EXISTS ".sqlprefix()."account_settings (
			id int NOT NULL AUTO_INCREMENT,
			account varchar(255) NOT NULL,
			setting_key varchar(255) NOT NULL,
			setting_value varchar(255) NOT NULL,
			PRIMARY KEY (id)
		)";
		/* @param1 String $sql_query
		 */
		$conn = sqlnew();
		$conn->query($query);
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		global $operserv; /* The global array of operserv.conf settings */
		
		/* Find our client OperServ
		 * Client::find(String $nick);
		 */
		$os = Client::find($operserv['nick']);

		/* Quit OperServ
		 * $client->quit(String $quit_message);
		 * 
		 * Default quit message is "Connection closed"
		 */
		$os->quit();

		/* We're deleting a hook that we added below in __init() :- "hook::del"
		 * hook::del(String @param1, String @param2);
		 * @param1 The SERVER hook called "connect"
		 * @param2 Reference to the method in this class
		 */
		hook::del("connect", 'operserv::spawn_client');
	}

	/* When our module gets fully loaded and recognised by the module manegement class and
	 * everything went well, here's where to run things that should otherwise be run after __construct
	 * i.e. spawning a client can be spammy in _construct if something went wrong
	 * 
	 * Return value: bool
	 * We return true here if everything went according to plan.
	 * We return false if something went wrong so we can clear up the module
	 * and unload it automatically
	 */
	function __init() : bool
	{
		/* We're hooking our function into the connection hook, :- "hook::func"
		 * hook::func(String @param1, String @param2);
		 * @param1 The SERVER hook called "connect"
		 * @param2 Reference to the method in this class
		 * 
		 * Spawning our client based on our operserv.conf
		 */
		hook::func("connect", 'operserv::spawn_client');

		/* If we're already connected when this module was loaded, spawn the client.
		 * If something goes wrong, unload the module and let log to the log-channel
		 */
		if (IsConnected())
			if (!operserv::spawn_client()) /* Spawning the client went wrong, return false */
				return false;

		/* Went okay, have a go at loading the other modules and return true.
		 * Other modules will return false if they fail and won't affect this module
		 */
		include("modules.conf");
		return true;
	}

	/* Method to actually spawn our client */
	static function spawn_client()
	{
		global $operserv; /* Our global array of operserv.conf options */
			
		/* spawn client and store it in $os
		 *
		 * new Client();
		 * @param1 String $nick
		 * @param2 String $ident
		 * @param3 String $hostmask
		 * @param4 NULL
		 * @param5 String $gecos
		 * @param6 String $module_name	(This module)
		 */
		$os = new Client($operserv['nick'],$operserv['ident'],$operserv['hostmask'],NULL,$operserv['gecos'],'operserv');

		/* If the client failed to register, return false */
		if (!$os)
			return false;
		
		/* All was fine, return true */
		return true;
	}

	function log($string)
	{
		global $operserv;
		$os = Client::find($operserv['nick']);
		$os->log($string);
	}
	
	/* hooking system you can copy and paste to yours with no edits needed */
	private static $actions = array();
	public static function run($hook, $args = array())
	{
		if (!empty(self::$actions[$hook]))
			foreach (self::$actions[$hook] as $f)
				$f($args);
	}

	public static function func($hook, $function)
	{
		self::$actions[$hook][] = $function;
	}
	public static function del($hook, $function)
	{
		for ($i = 0; isset(self::$actions[$hook][$i]); $i++)
			if (self::$actions[$hook][$i] == $function)
				array_splice(self::$actions[$hook],$i);
	}
}
