<?php

function AddServCmd
(
	String $modulehandle, /* Needs to be the name of a valid registered Module object */
	String $client, /* Needs to be a valid Client object (Our pseudoservers are type Client, normal users are type User) */
	String $cmd, /* The command word. Must not exceed 15 chars in length */
	String $function, /* The string reference to a method or function for the command */
	String $help_cmd_entry, /* The line which shows in the HELP response */
	String $syntax, /* Nothing more than a syntax to show the user for easy reference */
	String $extended_help /* Something to show if in the HELP <cmd> response. Use \n for a new line */
) : bool /* Returns true or false */
{
	/* ERROR LOL */
	$foundmodule = 0;
	foreach(Module::$modules as $m)
		if (!strcasecmp($m->name,$modulehandle))
			$foundmodule = 1;
			
	if (!$foundmodule)
	{
		SVSLog("Couldn't assign module data to non-exist module \"$modulehandle\".");
		return false;
	}
	/* check if we already have that command for that client */
	$list = ServCmd::$list;

	if (!empty($list))
		foreach ($list as $command)
		{
				if (!strcasecmp($command->client,$client) && !strcasecmp($command->command,$cmd))
				{
					SVSLog("Duplicate entry found for \"$cmd\" on client $command->client");
					return false;
				}
		}
	
	if (strlen($cmd) > 15)
	{
		SVSLog("Command \"$cmd\" is too long. Must not exceed 15 chars in length.");
		return false;
	}
	if (strpos($function,"::") == false)
	{
		SVSLog("Incorrect function reference. Must be a string in the form of 'class::function'");
		return false;
	}
	$tok = explode("::",$function);
	if (!method_exists($tok[0],$tok[1]))
	{
		SVSLog("Couldn't register command function. Unable to find method \"".$tok[1]."\" in class \"".$tok[0]."\"");
		return false;
	}
	if (!($piss = ServCmd::add_new_cmd($modulehandle, $client, $cmd, $function, $help_cmd_entry, $syntax, $extended_help)))
	{
		SVSLog("Could not add command for client $client: $piss", LOG_WARN);
		return false;
	}
	SVSLog("New command for $client: $cmd ($help_cmd_entry)");
	return true;	
}

class ServCmd {

	static $list = array();


	static function add_new_cmd($module, $client, $cmd, $func, $help_cmd_entry, $syntax, $extended_help)
	{
		/* check if client actually exists

		if (!Client::find($client))
			return "Client does not exist";
		
		** we don't check this part because of spawning issues... */

		$c = array();
		$c['mod_handle'] = $module;
		$c['client'] = $client;
		$c['function'] = $func;
		$c['command'] = $cmd;
		$c['help_cmd_entry'] = $help_cmd_entry;
		$c['syntax'] = $syntax;
		$c['extended_help'] = $extended_help;

		self::$list[] = new SCMD($c);
		return true;
	}
}

class SCMD
{
	function __construct(array $arr)
	{
		foreach($arr as $key => $value)
			$this->$key = $value;
	}
}

/* Unload commands associated with a mod we unloaded */
hook::func("unloadmod",
function($mod)
{
	foreach (ServCmd::$list as $i => $cmd)
		if (!strcmp($cmd->mod_handle,$mod[1]))
        {
            SVSLog("Command removed for $cmd->client: $cmd->command ($cmd->help_cmd_entry)");
			array_splice(ServCmd::$list,$i);
        }
});