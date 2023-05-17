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
/**
 * SREPLY is for sending Standard Replies to users. If you think this is useful \
 * please see methods send_fail, send_warn and send_note.
 * Syntax: `:123 SREPLY NICK F TOO_SPICY :WEE WOO`
 * 
 * @param parv[1]		Nick|UID
 * @param parv[2]		`F`, `W` or `N` for `FAIL`, `WARN` and `NOTE`.
 * @param parv[3]		The rest of the message
 */
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
	
	
	/** Sends a Standard Reply message as if it came from the client's server.
	 * Internal use only. Please see use ::send_fail, ::send_warn and ::send_note
	*/
	private static function send(
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

	/**
	 * Send FAIL to the user
	 * @param User $client The User object of the client to whom we're sending the FAIL
	 * @param String $command Indicates the user command which this reply is related to, or is * for messages initiated outside client commands (for example, an on-connect message).
	 * @param String $code Machine-readable reply code representing the meaning of the message to client software.
	 * @param String $context Optional parameters that give humans extra context as to where and why the reply was spawned (for example, a particular subcommand or sub-process).
	 * @param String $message A required plain-text message which is shown to users.
	 */
	public static function send_fail(User $client, String $command, String $code, String $context, String $message)
	{
		self::send($client, SRPL_FAIL, $code, $command, $context, $message);
	}
	/**
	 * Send WARN to the user
	 * @param User $client The User object of the client to whom we're sending the WARN
	 * @param String $command Indicates the user command which this reply is related to, or is * for messages initiated outside client commands (for example, an on-connect message).
	 * @param String $code Machine-readable reply code representing the meaning of the message to client software.
	 * @param String $context Optional parameters that give humans extra context as to where and why the reply was spawned (for example, a particular subcommand or sub-process).
	 * @param String $message A required plain-text message which is shown to users.
	 */
	public static function send_warn(User $client, String $command, String $code, String $context, String $message)
	{
		self::send($client, SRPL_WARN, $code, $command, $context, $message);
	}
	/**
	 * Send NOTE to the user
	 * @param User $client The User object of the client to whom we're sending the NOTE
	 * @param String $command Indicates the user command which this reply is related to, or is * for messages initiated outside client commands (for example, an on-connect message).
	 * @param String $code Machine-readable reply code representing the meaning of the message to client software.
	 * @param String $context Optional parameters that give humans extra context as to where and why the reply was spawned (for example, a particular subcommand or sub-process).
	 * @param String $message A required plain-text message which is shown to users.
	 */
	public static function send_note(User $client, String $command, String $code, String $context, String $message)
	{
		self::send($client, SRPL_NOTE, $code, $command, $context, $message);
	}
}
