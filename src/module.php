<?php

/* Modular moduly */

class Module {

	public $success = false;

	/* construct0r */
	function __construct($mod)
	{
		global $os;

		$err = false;
		
		/* We take care of that fam */
		if (mb_substr($mod,-4) == ".php")
		{
			if (isset($os)) SVSLog("Could not load module: $mod. Remember NOT to use \".php\" when using loadmodule()\n");
			$err = true;
		}
		/* Couldn't find it anyway, return */
		elseif (!($module = $this->find_module($mod)))
		{
			if (isset($os)) SVSLog("Could not find module: $mod. Please check your configuration.\n");
			$err = true;
		}
		/* Ay we got it, load it */
		elseif ($module)
		{
			
			if (!($this->load_module($mod)))
			{
				if (isset($os)) SVSLog("ERROR: Could not load module. Could not find class called $mod\n");
				$err = true;
			}
		}
		
		return;
	}
	
	/* function to find de m0dule */
	function find_module($mod)
	{
		$mod .= ".php";
		$dir = getcwd()."/src/modules/";
		if (!file_exists($dir.$mod))
			return false;			
		
		return true;
	}
	
	/* function to load de m0dule */
	function load_module($mod)
	{
		global $modules;
		$os = Client::find("OperServ");
		/* initiate array */
		if (!isset($modules))
			$modules = array();
		
		$dir = getcwd()."/src/modules/";

		$tok = explode("/",$mod);
		$modname = $tok[count($tok) - 1];
		/* Module was already loaded */
		foreach ($modules as $m)
		{
			if ($m->name == $modname)
				return true;

		}
		include_once("$dir$mod.php");

		/* Class not found */
		$module = new $modname();
		if (!$module->name)
			return false;
		
		if ($module->name !== get_class($module))
			return false;

		/* Found the class, check its contents */		
		else {

			$missing = array();

			if(!isset($module->author) || strlen($module->author) == 0)
				$missing[] = "author";

			if (!isset($module->name) || strlen($module->name) == 0)
				$missing[] = "name";

			if (!isset($module->description) || strlen($module->description) == 0)
				$missing[] = "description";
			
			if (!isset($module->version) || strlen($module->version) == 0)
				$missing[] = "version";
				
			if (!empty($missing))
			{
				if (isset($os))
				{
					foreach($missing as $i)
						echo "ERROR: $i was not specified in the class header. please set \$$i in the header.\n";
					if (isset($os)) SVSLog("Errors were found and specified above. Please correct.\n");
					return false;
				}
			}

			/* Register it properly */			
			$modules[] = $module;

			if (!$module->__init())
			{
				if (isset($os)) SVSLog("Couldn't initialise module: $module->name");
				return false;
			}
			$this->success = true;
			return true;
		}
	}
}

function loadmodule($mod){
	global $modules,$os;
	$load = new Module($mod);
	if (!$load->success)
	{
		SVSLog("Could not load module: $mod");
		return false;
	}
	else
	{
		SVSLog("Loaded module: $mod");
		return true;
	}
}

function unloadmodule($mod){
	global $modules;
	if (!$mod)
	{
		SVSLog("Just attempted to unload all modules with NULL modinfo. This action has been cancelled.");
		return;
	}
	for ($i = 0; isset($modules[$i]); $i++)
		if (get_class($modules[$i]) == $mod)
		{
			$modules[$i] = NULL;
			array_splice($modules,$i);
			$i--;
			SVSLog("Unloaded module: $mod");
		}
	foreach (cmd::$commands as $id => $cmd)
		if ($cmd['mod'] == $mod)
		{
			cmd::$commands[$id] = NULL;
			unset(cmd::$commands[$id]);
		}
	hook::run("unloadmod", $mod);
}

function module_exists($modname)
{
	global $modules;
	foreach ($modules as $m)
	{
		if ($m->name == $modname)
			return true;
	}
	return false;
}

function require_module($module)
{
	if (!module_exists($module))
		die("FATAL ERROR: Tried to load module which requires $module\n");
}