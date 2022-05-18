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
<<<<<<< HEAD
			if (isset($os)) SVSLog("Could not load module: $mod. Remember NOT to use \".php\" when using loadmodule()\n");
=======
			if (isset($os)) $os->log("Could not load module: $mod. Remember NOT to use \".php\" when using loadmodule()\n");
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
			$err = true;
		}
		/* Couldn't find it anyway, return */
		elseif (!($module = $this->find_module($mod)))
		{
<<<<<<< HEAD
			if (isset($os)) SVSLog("Could not find module: $mod. Please check your configuration.\n");
=======
			if (isset($os)) $os->log("Could not find module: $mod. Please check your configuration.\n");
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
			$err = true;
		}
		/* Ay we got it, load it */
		elseif ($module)
		{
			
			if (!($this->load_module($mod)))
			{
<<<<<<< HEAD
				if (isset($os)) SVSLog("ERROR: Could not load module. Could not find class called $mod\n");
=======
				if (isset($os)) $os->log("ERROR: Could not load module. Could not find class called $mod\n");
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
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
<<<<<<< HEAD
		global $modules;
		$os = Client::find("OperServ");
=======
		global $modules,$os;

>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
		/* initiate array */
		if (!isset($modules))
			$modules = array();
		
		$dir = getcwd()."/src/modules/";

		$tok = explode("/",$mod);
		$modname = $tok[count($tok) - 1];
<<<<<<< HEAD
=======
	
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
		/* Module was already loaded */
		foreach ($modules as $m)
		{
			if ($m->name == $modname)
				return true;
<<<<<<< HEAD

=======
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
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
<<<<<<< HEAD
					if (isset($os)) SVSLog("Errors were found and specified above. Please correct.\n");
=======
					if (isset($os)) $os->log("Errors were found and specified above. Please correct.\n");
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
					return false;
				}
			}

			/* Register it properly */			
			$modules[] = $module;

			if (!$module->__init())
			{
<<<<<<< HEAD
				if (isset($os)) SVSLog("Couldn't initialise module: $module->name");
=======
				if (isset($os)) $os->log("Couldn't initialise module: $module->name");
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
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
<<<<<<< HEAD
		SVSLog("Could not load module: $mod");
		return false;
	}
	else
	{
		SVSLog("Loaded module: $mod");
		return true;
	}
=======
		if (isset($os))
			$os->log("Could not load module: $mod");
		return false;
	}
	if (isset($os))
		$os->log("Loaded module: $mod");
	return true;
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
}

function unloadmodule($mod){
	global $modules,$os;
	for ($i = 0; isset($modules[$i]); $i++)
		if (get_class($modules[$i]) == $mod)
		{
			$modules[$i] = NULL;
			array_splice($modules,$i);
			$i--;
<<<<<<< HEAD
			SVSLog("Unloaded module: $mod");
=======
			$os->log("Unloaded module: $mod");
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
		}
	foreach (cmd::$commands as $id => $cmd)
		if ($cmd['mod'] == $mod)
		{
			cmd::$commands[$id] = NULL;
			unset(cmd::$commands[$id]);
		}
<<<<<<< HEAD
	hook::run("unloadmod", $mod);
=======
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
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

<<<<<<< HEAD
function require_module($module)
{
	if (!module_exists($module))
		die("FATAL ERROR: Tried to load module which requires $module\n");
}
=======
?>
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
