<?php

/* Modular moduly */

class Module
{
	/* for the module to indicate if it was successful in loading or not lol */
	public $success = false;

	/* this is a static list of de moduels */
	static $modules = [];

	/* construct0r */
	function __construct($mod)
	{

		/* We take care of that fam */
		if (mb_substr($mod,-4) == ".php")
		{
			if (isset($os)) SVSLog("Could not load module: $mod. Remember NOT to use \".php\" when using loadmodule()\n");
			
		}
		/* Couldn't find it anyway, return */
		elseif (!($module = $this->find_module($mod)))
		{
			if (isset($os)) SVSLog("Could not find module: $mod. Please check your configuration.\n");
			
		}
		/* Ay we got it, load it */
		elseif ($module)
			if (!($this->load_module($mod)))
			{
				if (isset($os)) SVSLog("ERROR: Could not load module. Could not find class called $mod\n");
				
			}
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
		global $operserv;
		if (isset($operserv['nick']))
			$os = Client::find($operserv['nick']);
		
		
		$dir = getcwd()."/src/modules/";

		$tok = explode("/",$mod);
		$modname = $tok[count($tok) - 1];
		/* Module was already loaded */
		foreach (Module::$modules as $m)
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
			Module::$modules[] = $module;

			if (!$module->__init())
			{
				if (isset($os)) SVSLog("Couldn't initialise module: $module->name");
				return false;
			}
			$this->success = true;
			return true;
		}
	}

	static function modules_list($id, $params)
	{
		$reply = rpc_new_reply();
		rpc_append_result($reply, Module::$modules);
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
		SVSLog("[RPC] Request to list modules");
	}

	/* RPC functions */
	static function module_load($id, $params)
	{
		$reply = rpc_new_reply();
		if (count($params) != 1)
		{
			rpc_append_error($reply, "You may only specify one module per request", RPC_ERR_SEVER_ERROR);
			rpc_append_id($reply, $id);
			rpc_send_reply($id, $reply);
			SVSLog("[RPC] Erroneous module.load request: Too many targets");
			return;
		}
		elseif (!loadmodule($params['module']))
		{
			rpc_append_error($reply, "Module failed to load \"".$params['module']."\"", RPC_ERR_SEVER_ERROR);
			rpc_append_id($reply, $id);
			rpc_send_reply($id, $reply);
			SVSLog("[RPC] Erroneous module.load request: Unknown reason");
			return;
		}

		rpc_append_result($reply, "Module \"".$params['module']."\" loaded successfully");
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
		SVSLog("[RPC] Module loaded via module.load: \"".$params['module']."\"");
	}
	static function module_unload($id, $params)
	{
		$reply = rpc_new_reply();
		if (count($params) != 1)
		{
			rpc_append_error($reply, "You may only specify one module per request", RPC_ERR_SEVER_ERROR);
			rpc_append_id($reply, $id);
			rpc_send_reply($id, $reply);
			SVSLog("[RPC] Erroneous module.unload request: Too many targets");
			return;
		}
		elseif (!unloadmodule($params['module']))
		{
			rpc_append_error($reply, "Module failed to unload \"".$params['module']."\": Module was not loaded", RPC_ERR_SEVER_ERROR);
			rpc_append_id($reply, $id);
			rpc_send_reply($id, $reply);
			SVSLog("[RPC] Erroneous module.unload request: Module was not unloaded");
			return;
		}

		rpc_append_result($reply, "Module \"".$params['module']."\" unloaded successfully");
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
		SVSLog("[RPC] Module unloaded via module.unload: \"".$params['module']."\"");
	}
}

$err = NULL;
if (!RPCHandlerAdd(NULL, 'module.list', 'Module::modules_list', $err))
	die("[RPC] Could not load handler for \"module.list\": $err\n");

if (!RPCHandlerAdd(NULL, 'module.load', 'Module::module_load', $err))
	die("[RPC] Could not load handler for \"module.list\": $err\n");

if (!RPCHandlerAdd(NULL, 'module.unload', 'Module::module_unload', $err))
	die("[RPC] Could not load handler for \"module.list\": $err\n");

function loadmodule($mod)
{

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

function unloadmodule($mod)
{

	if (!$mod)
	{
		SVSLog("Just attempted to unload all modules with NULL modinfo. This action has been cancelled.");
		return;
	}
	for ($i = 0; !empty(Module::$modules[$i]); $i++)
		if (get_class(Module::$modules[$i]) == $mod)
		{
			Module::$modules[$i] = NULL;
			Module::$modules = array_values(Module::$modules);
			$i--;
			SVSLog("Unloaded module: $mod");
		}
	foreach (cmd::$commands as $id => $cmd)
		if ($cmd['mod'] == $mod)
		{
			cmd::$commands[$id] = NULL;
			unset(cmd::$commands[$id]);
		}
	RPC::unload_by_module($mod);
	hook::run("unloadmod", $mod);
	return true;
}

function module_exists($modname)
{
	foreach (Module::$modules as $m)
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
