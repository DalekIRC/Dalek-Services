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
\\	Title:		REHASH
//				
\\	Desc:		REHASH compatibility
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
class rehash {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "rehash";
	public $description = "Provides REHASH compatibility";
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

		if (!CommandAdd($this->name, 'REHASH', 'rehash::cmd_rehash', 1))
			return false;

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public function cmd_rehash($u)
	{
		/* User object of caller */
		$nick = $u['nick'];

		S2S("382 $nick->nick :Rehashing conf/dalek.conf");
		$errors = "";

		if (!do_rehash($errors))
		{
			SVSLog("Could not rehash", LOG_WARN);
			SVSLog($errors, LOG_WARN);

			sendnotice($nick, NULL, NULL, "Could not rehash; errors occurred:");
			sendnotice($nick, NULL, NULL, "$errors");
		
		}
		else
		{
			sendnotice($nick, NULL, NULL, "Rehashed successfully");
			SVSLog("Rehashed successfully");
		}
		/* You don't HAVE to return, butt-fuck it */
		return;
	}
}

// if our function doesn't exist, add it uhuhu
if (!function_exists('do_rehash'))
{
	// @param = Memory of empty array that we will fill with errors if there are any
	function do_rehash(String &$errors) : bool
	{
		$oldfile = DALEK_CONF_DIR . "/dalek.conf";
		$newfile = $oldfile.".".servertime();
		
		// we are essentially going to reload the file with a new name so as to just update the
		// variables we hold for the config
		if (!is_file($oldfile))
		{
			$errors = "Couldn't find the dalek.conf file";
			return false;
		}
		if (!($file = fopen($newfile, 'c')))
		{
			$errors = "Could not open temporary file";
			return false;
		}
		if (!fwrite($file, file_get_contents($oldfile)))
		{
			$errors = "Could not write to temporary file";
			return false;
		}
		fclose($file);

		if (!is_file($newfile))
		{
			$errors = "Could not find the file we just created. Awkward!";
			return false;
		}

		include($newfile);
		unlink($newfile); // delete the new file lol
		$empty = [];
		hook::run(HOOKTYPE_REHASH, $empty);
		return true;		
	}
}
