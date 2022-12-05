<?php
/*				
//	(C) 2022 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title:		PING
//				
\\	Desc:		PING command
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

/* class name needs to be the same name as the file */
class ping {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ping";
	public $description = "Provides PING compatibility";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;


	public static $list = [];
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
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		/* Params: CommandAdd( this module name, command keyword, function, parameter count)
		 * the function is a string reference to this class, the cmd_elmer method (function)
		 * The last param is expected parameter count for the command
		 * (both point to the same function which determines)
		*/

		if (!CommandAdd($this->name, 'PING', 'ping::cmd_ping', 1) ||
			!CommandAdd($this->name, 'PONG', 'ping::pong', 1))
			return false;

		$errors = [];
		if (Events::Add(servertime() + 60, 0, 60, 'ping::event', [], $errors) &&
			Events::Add(servertime() + 10, 0, 1, 'ping::timeout_check', [], $errors) &&
			!empty($errors))
		{
			foreach($errors as $e)
				DebugLog($e);
			return false;
		}
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_ping($u)
	{
		$arr = ['token' => $u['dest']];
		hook::run(HOOKTYPE_PING, $arr);
		S2S("PONG ".$u['dest']); 	// Ping it back
	}

	public static function pong($u)
	{
		if (mb_substr($u['dest'], 1) == ping::$list[0]->id)
			ping::$list = [];
	}

	public static function event()
	{
		SVSLog("Hit event???");
	}
	public static function timeout_check()
	{

	}
}

class _Ping
{
	public $id = 0;
	public $destination = NULL;
	public $ctime = 0;
	public $cookie = NULL;

	function __construct($dest)
	{
		if (!empty(ping::$list))
		{
			$a[] = ['err' => "Ping timeout"];
			hook::run(HOOKTYPE_ERROR, $a);
		}
		else {
			$this->id = base64_encode(microtime(true));
			$this->destination = $dest;
			$this->ctime = servertime();
			ping::$list[] = $this;
			S2S("PING $this->destination :$this->id");
			Events::Add(servertime() + 60, 1, 1, 'ping::event', [], $errors);
		}
	}
}