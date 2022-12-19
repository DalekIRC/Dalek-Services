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
\\	Title:		SREPLY
//				
\\	Desc:		Standard Reply command
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/
define('SRPL_FAIL', 'F');
define('SRPL_WARN', 'W');
define('SRPL_NOTE', 'N');

/* class name needs to be the same name as the file */
class sreply {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "sreply";
	public $description = "Provides compatibility with IRCv3's Standard Replies";
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
	/* Initialisation */
	function __init()
	{
		return true;
	}

	/** SREPLY
	 * @param parv[1]		Nick|UID
	 * @param parv[2]		"F", "W" or "N" for FAIL, WARN and NOTE.
	 * @param parv[3]		The rest of the message
	
	 */
	/** Sends a Standard Reply message as if it came from the client's server. */
	public static function send(
		User $client,
		String $type,
		String $command,
		String $code,
		String $context = NULL,
		String $message
	){
		$c = ($context) ? "$context " : "";
		S2S("SREPLY $client->uid $type $command $code $c:$message");
	}

	public static function send_fail(User $client, String $command, String $code, String $context = "", String $message)
	{
		self::send($client, SRPL_FAIL, $code, $command, $context, $message);
	}
	public static function send_warn(User $client, String $command, String $code, String $context = "", String $message)
	{
		self::send($client, SRPL_WARN, $code, $command, $context, $message);
	}
	public static function send_note(User $client, String $command, String $code, String $context = "", String $message)
	{
		self::send($client, SRPL_NOTE, $code, $command, $context, $message);
	}
}
