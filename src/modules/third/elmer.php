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
\\	Title:		elmer
//				
\\	Desc:		Example module for template purposes
\\				Adds ELMER compatibility to services
//				But WHY Valware?! Just why?! For example purposes.
\\				Still funny asf
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/
/* Just place this in src/third and add this line to dalek.conf:
	loadmodule("third/elmer");
 */

/* class name needs to be the same name as the file */
class elmer {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "elmer";
	public $description = "An example of how to write a module which makes services talk like elmer";
	public $author = "Valware";
	public $version = "1.0";

	/* The array we will be using to store elmer'd nicks in */
	public static $elmer = array();

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		/* We'll be storing elmer'd nicks in an array mentioned above, so */
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		/* Gets rid of $elmer array automatically */
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		/* Params: CommandAdd( this module name, command keyword, function, parameter count) */
		/* the function is a string reference to this class, the cmd_elmer method (function) */
		/* The last param is expected parameter count for the command */
		/* (both point to the same function which determines) */

		if (!CommandAdd($this->name, 'ELMER', 'elmer::cmd_elmer', 1))
			return false;

		if (!CommandAdd($this->name, 'DELMER', 'elmer::cmd_elmer', 1))
			return false;
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public function cmd_elmer($u)
	{
		/* Get the command that called us */
		$cmd = $u['cmd'];

		/* User object of caller */
		$nick = $u['nick'];

		/* Tokenise the incoming string into $parv */ 
		$parv = explode(" ",$u['params']);
		
		/* errors and shit lol */
		if (count($parv) < $u['parc'])
		{
			S2S("461 $nick->nick $cmd :Need more parameters.");
			return;
		}

		/* Check which command we got */
		if ($cmd !== "elmer" && $cmd !== "delmer")
			return;

		/* Are we adding or removing */
		$add = ($cmd == "elmer") ? true : false;

		/* Locating target, beep bzzzzzz errchhh *fax sounds* */
		$target = new User($u['params']);

		/* If we're adding and they're not already elmer'd */
		if ($add)
		{
			/* If they are already elmer'd */
			if (IsElmer($target))
			{
				S2S("NOTICE $nick->uid :$target->nick is already talking like Elmer.");
				return;
			}
			/* Let them know and update the array */
			S2S("NOTICE $nick->uid :$target->nick is now talking like Elmer.");
			array_push(self::$elmer,strtolower($target->nick));
		}

		/* Looks like we removing instead =] */
		else
		{
			if (!IsElmer($target))
			{
				S2S("NOTICE $nick->uid :$target->nick wasn't talking like Elmer anyway.");
				return;
			}
			/* Let them know and update the array */
			S2S("NOTICE $nick->uid :$target->nick is no longer talking like Elmer.");

			foreach(self::$elmer as $val => $key) /* loop it */
			  if ($key == strtolower($target->nick)) /* Find it */
				array_splice(self::$elmer,strtolower($val)); /* delete it */
		}

		/* You don't HAVE to return, butt-fuck it */
		return;
	}
}

/* Function to check if user is already elmer
 * IsElmer($nick)
 * returns true or false
*/
function IsElmer(User $client) : bool
{
	if (!isset(elmer::$elmer))
		return false;
	if (in_array(strtolower($client->nick),elmer::$elmer))
		return true;
	return false;
}
