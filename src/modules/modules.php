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
\\	Title:		MODULE
//				
\\	Desc:		MODULE compatibility
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
class modules {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "modules";
	public $description = "Provides MODULE command";
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

		if (!CommandAdd($this->name, 'MODULE', 'modules::cmd_module', 2))
			return false;

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_module($u)
	{
		/* Get the command that called us */
		$cmd = $u['cmd'];

		/* User object of caller */
		$nick = $u['nick'];

		/* Tokenise the incoming string into $parv */ 
		$parv = explode(" ",$u['params']);
		if ($parv[0] !== "-all")
        {
    		modules::module_response($nick, "Showing loaded 3rd party modules (use \"MODULE -all ".Conf::$settings['info']['services-name']."\" to show all modules):");
            foreach (Module::$modules as $m)
			{
				if (isset($m->official))
					continue;
				$third = (isset($m->official) && $m->official == true) ? "[OFFICIAL]" : "[3RD]";
                modules::module_response($nick, "*** $m->name v$m->version - $m->description - by $m->author $third");
			}
            modules::module_response($nick, "End of modules list");
        }

        else {
            modules::module_response($nick, "Showing ALL loaded modules");
            foreach (Module::$modules as $m)
			{
				$third = (isset($m->official) && $m->official == true) ? "[OFFICIAL]" : "[3RD]";
                modules::module_response($nick, "*** $m->name v$m->version - $m->description - by $m->author $third");
			}
			modules::module_response($nick, "End of modules list");
        }
		return;
	}
    static function module_response(User $nick, $string)
    {
    	S2S("304 $nick->nick :$string");
    }
}
