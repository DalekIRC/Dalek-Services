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
\\	Title:		SMOD
//				
\\	Desc:		SMOD command
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
class smod {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "smod";
	public $description = "Provides SMOD compatibility";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/**
	 * Static list of uplinks modules
	 */
	public static $list = array();

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
		CommandAdd('smod', "SMOD", 'smod::cmd_smod', 25);
		hook::func(HOOKTYPE_START, 'smod::hook_start');
		hook::func(HOOKTYPE_BURST, 'smod::hook_burst');
		if (IsConnected())
		{
			self::hook_burst([]);
		}
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_smod($u)
	{
		$parv = split($u['raw']);
		$parv[0] = NULL;
		$parv[1] = NULL;
		$parv = split(mb_substr(glue($parv),1));
		for ($i = 0; $i < count($parv); $i++)
		{
			$tok = split($parv[$i], ":");
			$obj = (object) [];
			$obj->letter = $tok[0];
			$obj->name = $tok[1];
			$obj->version = $tok[2];
			self::$list[$obj->name] = $obj;
			$str = var_export($obj);
		}
	}

	public static function hook_start($u)
	{
		$found = 0;
		foreach (self::$list as $key => $module)
			if ($key == "third/dalek")
				$found++;

		var_dump(self::$list);
		if (!$found)
		{
			sendto_sno("S", "Warning: UnrealIRCd module \"third/dalek\" is not present on our uplink. This will cause features to stop working properly.");
			SVSLog(bold("WARNING: ") . "The DalekIRC UnrealIRCd module does not appear to be loaded on our uplink. This will cause features to stop working properly.");
		}
		else
		{
			sendto_sno("S", "Found UnrealIRCd module \"third/dalek\"... Good!");
			SVSLog("Found module third/dalek on our uplink. Good!");
		}
	}
	public static function hook_burst($serv)
	{
		$serv->sendraw("SMOD :G:third/dalek:1.0.0");
	}
}
