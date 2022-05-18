<?php
<<<<<<< HEAD

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
\\	Title: NickServ
//	
\\	Desc: NickServ
//	
\\		This also is the official documented template
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
include("nickserv.conf");

/* Our module filename needs to be the same as the class and must be unique
 * This allows for easy modularity, for loading and unloading your module.
 */
class nickserv {

	/* Module handle */
	public $name = "nickserv"; 	/* $name needs to be the same name as the class and file lol */
	public $description = "NickServ PseudoClient"; /* $description of what the module does. This will show in the MODULE response to the server. */
	public $author = "Valware"; /* Who you are */
	public $version = "1.0"; /* The version of the module. You should increment this if you change it. */
	public $official = true; /* This marks the module as official. You should not specify this if you are writing a module for Dalek-Contrib */

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		/* Creating out tables for account settings which apply to IRC. */
		$query = "CREATE TABLE IF NOT EXISTS dalek_account_settings (
			id int NOT NULL AUTO_INCREMENT,
			account varchar(255) NOT NULL,
			setting_key varchar(255) NOT NULL,
			setting_value varchar(255) NOT NULL,
			PRIMARY KEY (id)
		)";
		/* @param1 String $sql_query
		 */
		SQL::query($query);
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		global $nickserv; /* The global array of nickserv.conf settings */
		
		/* Find our client NickServ
		 * Client::find(String $nick);
		 */
		$ns = Client::find($nickserv['nick']);

		/* Quit NickServ
		 * $client->quit(String $quit_message);
		 * 
		 * Default quit message is "Connection closed"
		 */
		$ns->quit();

		/* We're deleting a hook that we added below in __init() :- "hook::del"
		 * hook::del(String @param1, String @param2);
		 * @param1 The SERVER hook called "connect"
		 * @param2 Reference to the method in this class
		 */
		hook::del("connect", 'nickserv::spawn_client');
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
		 * Spawning our client based on our nickserv.conf
		 */
		hook::func("connect", 'nickserv::spawn_client');

		/* If we're already connected when this module was loaded, spawn the client.
		 * If something goes wrong, unload the module and let log to the log-channel
		 */
		if (IsConnected())
			if (!nickserv::spawn_client()) /* Spawning the client went wrong, return false */
				return false;

		/* Went okay, have a go at loading the other modules and return true.
		 * Other modules will return false if they fail and won't affect this module
		 */
		include("modules.conf");
		return true;
	}

	/* Method to actually spawn our client */
	function spawn_client()
	{
		global $nickserv; /* Our global array of nickserv.conf options */
			
		/* spawn client and store it in $ns
		 *
		 * new Client();
		 * @param1 String $nick
		 * @param2 String $ident
		 * @param3 String $hostmask
		 * @param4 NULL
		 * @param5 String $gecos
		 * @param6 String $module_name    (This module)
		 */
		$ns = new Client($nickserv['nick'],$nickserv['ident'],$nickserv['hostmask'],NULL,$nickserv['gecos'],'nickserv');

		/* If the client failed to register, return false */
		if (!$ns)
			return false;
		
		/* All was fine, return true */
		return true;
	}

	function log($string)
	{
		global $nickserv;
		$ns = Client::find($nickserv['nick']);
		$ns->log($string);
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
=======
/*				
//	(C) 2021 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\							v3
//				
\\				
//				
\\	Title:		NickServ
//				
\\	Desc:		Provides the bare essentials for
//				pseudoclient NickServ, the
\\				Nickname Registration Service.
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

// NickServ configuration
include "class.php";
include "nickserv.conf";
include "modules.conf";


// Spawn nickserv on server connect
hook::func("connect", function($u){
		global $nickserv,$ns;
		
		// spawn client with $ns
		$ns = new Client($nickserv['nick'],$nickserv['ident'],$nickserv['hostmask'],$nickserv['gecos']);
		
});


hook::func("privmsg", function($u){
	
	global $ns,$nickserv;
	if (strpos($u['dest'],"@") !== false){
		$n = explode("@",$u['dest']);
		$dest = $n[0];
	}
	else { $dest = $u['dest']; }
	
	
	if (strtolower($dest) == strtolower($ns->nick) || $dest == $nickserv['uid']){ 
		nickserv::run("privmsg", array(
			"msg" => $u['parv'],
			"nick" => $u['nick'])
		);
			
	}
	
});
hook::func("preconnect", function($u){
	
	global $sql;
	
	$query = "CREATE TABLE IF NOT EXISTS dalek_accounts (
		id int NOT NULL AUTO_INCREMENT,
		timestamp varchar(12) NOT NULL,
		display varchar(255) NOT NULL,
		email varchar(255) NOT NULL,
		pass varchar(255) NOT NULL,
		PRIMARY KEY (id)
	)";
	$sql::query($query);
	
	$query = "CREATE TABLE IF NOT EXISTS dalek_account_settings (
		id int NOT NULL AUTO_INCREMENT,
		account varchar(255) NOT NULL,
		setting_key varchar(255) NOT NULL,
		setting_value varchar(255) NOT NULL,
		PRIMARY KEY (id)
	)";
	$sql::query($query);	
});

	
nickserv::func("privmsg", function($u){
	
	global $ns,$cf;
	
	$parv = explode(" ",$u['msg']);
	$nick = $u['nick'];
	if ($parv[0] == chr(1)."VERSION".chr(1)){
		$ns->notice($nick,chr(1)."VERSION Dalek IRC Services v0.2 on ".$cf['servicesname']." Protocol: ".$cf['proto'].chr(1));
		return;
	}
	
	if ($parv[0] == chr(1)."PING"){
		if (!is_numeric(str_replace(chr(1),"",$parv[1]))){ $ns->sendraw(":69L SVSKILL $nick :Client misbehaving"); return; }
		$ns->notice($nick,chr(1)."PING ".$parv[1].chr(1));
		return;
	}
});
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
