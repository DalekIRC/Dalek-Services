<?php
/*				
//	(C) 2021 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\							v3
//				
\\				
//				
\\	Title:		Hook
//				
\\	Desc:		Server hooks. This is the function that
//				is used when calling and running hooks.
\\				
//	Examples:	hook::func("privmsg", function($u){});
\\				hook::run("privmsg", array());
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/


/** Hooktype Definitions */
/** HOOKTYPE_RAW:
 * @param = array("uid", "dest", "parv")
 * "uid" = The UID (or SID) of the caller
 * "dest" = The raw destination 
 */
define('HOOKTYPE_RAW', 'raw');
/** When a new message is sent, regardless of if it's a channel or user message or notice */
define('HOOKTYPE_NEW_MESSAGE', 'privmsg');
/** When a new message is sent to a pseudoclient on our server */
define('HOOKTYPE_USER_MESSAGE', 'usermsg');
/** When a TAGMSG is sent to a channel we are in, or sent to a pseudoclient  (somewhere we can see it) */
define('HOOKTYPE_TAGMSG', 'tagmsg');
/** When a new message is sent to a channel we can see the messages for */
define('HOOKTYPE_CHANNEL_MESSAGE', 'chanmsg');
/** Before our server attempts to open a connection to the IRCd */
define('HOOKTYPE_PRE_CONNECT', 'preconnect');
/** When we have successfully opened our socket, before we have finished syncing with the server */
define('HOOKTYPE_CONNECT', 'connect');
/** When we have successfully finished syncing with the server and we are good to go. */
define('HOOKTYPE_START', 'start');
/** When a notice gets received from a user */
define('HOOKTYPE_NOTICE', 'notice')
/** When a user joins a channel */;
define('HOOKTYPE_JOIN', 'join');
/** When a user parts a channel */ 
define('HOOKTYPE_PART', 'part');
/** When a user disconnects from the network */
define('HOOKTYPE_QUIT', 'quit');
/** When a CTCP is received */
define('HOOKTYPE_CTCP', 'ctcp');
/** When we recieve a CTCP reply */
define('HOOKTYPE_CTCPREPLY', 'ctcpreply');
/** When one or more channel modes were set */
define('HOOKTYPE_CHANNELMODE', 'mode');
/** Individual channel mode with params */
define('HOOKTYPE_CHANNELMODE_PARAM', 'cmodeparam');
/** When one or more usermodes were set */
define('HOOKTYPE_UMODE_CHANGE', 'umode');
/** When a user gets kicked from a channel */
define('HOOKTYPE_KICK', 'kick');
/** When we hear about a TKL */
define('HOOKTYPE_TKL', 'tkl'); 
/** When a user auths with services */
define('HOOKTYPE_AUTHENTICATE', 'auth'); 
/** A continuation from a SASL we already dealt with */
define('HOOKTYPE_SASL_CONTINUATION', 'sasl_cont'); 
/** Handle what happens after the result of their SASL has been processed */
define('HOOKTYPE_SASL_RESULT', 'sasl_result'); 
/** When we get a ping. This is usually used for piggybacking a regular timed event */
define('HOOKTYPE_PING', 'ping'); 
/** When we have a new user (local or remote) */
define('HOOKTYPE_WELCOME', 'UID'); 
/** When a new server is introduced to us */
define('HOOKTYPE_SERVER_CONNECT', 'SID'); 
/** When we are being told about the entire contents of a channel (users)*/
define('HOOKTYPE_SJOIN', 'SJOIN'); 
/** When a channel is created */
define('HOOKTYPE_CHANNEL_CREATE', 'newchan'); 
/** When a channel is destroyed */
define('HOOKTYPE_CHANNEL_DESTROY', 'destroychan'); 
/** For RPC calls (not for IRC) */
define('HOOKTYPE_RPC_CALL', 'rpc_call'); 
/** For usermode changes. This is run for both User and Client types */
define('HOOKTYPE_USERMODE', 'umode'); 
/** Hook for processing IRCv3 "METADATA" */
define('HOOKTYPE_METADATA', 'usermeta');
/* This hook runs with there is an update available for Dalek */
define('HOOKTYPE_UPDATE_FOUND', 'update_found');
/** This hook runs when the server we have just linked with has finished syncing */
define('HOOKTYPE_EOS', 'eos');
/** Runs when Dalek has received a REHASH signal */
define('HOOKTYPE_REHASH', 'rehash');
/** When we receive an MD for a user we can process it here */
define('HOOKTYPE_USER_MD', 'usermd');
/** When we receive an MD for a channel we can process it here */
define('HOOKTYPE_CHANNEL_MD', 'chanmd');
/** This is a hook you can use to make sure your module has correctly
  * found any configuration items that may be required */
define('HOOKTYPE_CONFIGTEST', 'cfgtest');
/** This hook is where you can define your defaults and such for the configuration file */
define('HOOKTYPE_CONFIGRUN', 'cfgrun');
/** To run as soon as we have connected, while syncing, before sending our own EOS */
define('HOOKTYPE_BURST', 'servburst');
/** This is run when a module is unloaded. */
define('HOOKTYPE_UNLOAD_MODULE', 'unloadmodule');
/** When a connection error occurred */
define('HOOKTYPE_ERROR', 'conn_err');

/** 
 *  Class for "hook"
 * This is the main function which gets called whenever you want to use a hook.
 * Example:
 * Calling the hook:
 * hook::func(HOOKTYPE_REHASH, 'bob');
 * 
 * This hook references the function 'bob', and will run this
 * function bob
 * {
 * 	echo "We rehashed!";
 * }
 * 
 * Running a hook:
 * $array = ["sid" => "69L"]; // the information to pass through the hook
 * hook::run(HOOKTYPE_EOS, $array);
 * 
 * 4th December 2022:
 * Moving this over to internal use only, see HookAdd() instead.
 */
class hook {

	/** A static list of hooks and their associated functions */
	private static $actions = [];

	/** Runs a hook.
	 * The parameter for $hook should be a "HOOKTYPE_" as defined in hook.php
	 * @param string $hook The define or string name of the hook. For example, HOOKTYPE_REHASH.
	 * @param array &$args The array of information you are sending along in the hook, so that other functions may see and modify things.
	 * @return void Does not return anything.
	 * 
	 */
	public static function run($hook, &$args = array())
	{
		if (!empty(self::$actions[$hook]))
			foreach (self::$actions[$hook] as &$f)
				$f($args);
			
	}

	/** Calls a hook
	 * @param string $hook The define or string name of the hook. For example, HOOKTYPE_REHASH.
	 * @param string|Closure $function This is a string reference to a Closure function or a class method.
	 * @return void Does not return anything.
	 */
	public static function func($hook, $function) {
		self::$actions[$hook][] = $function;
	}

	/** Deletes a hook
	 * @param string $hook The hook from which we are removing a function reference.
	 * @param string $function The name of the function that we are removing.
	 * @return void Does not reuturn anything.
	 */

	public static function del($hook, $function) {
		for ($i = 0; isset(self::$actions[$hook][$i]); $i++)
		  if (self::$actions[$hook][$i] == $function)
		  array_splice(self::$actions[$hook],$i);
	}
}

/** UnrealIRCd inspired HookAdd()
 * Validate hooks when adding them
 * @param object $mod The module for which this hook was added.
 * @param string $hook The HOOKTYPE
 * @param int $priority The priority of the hook.
 * @param callable $priority
 * 
 * @return bool Whether or not the hook was added successfully
 */

 function HookAdd(object $mod = NULL, String $hook, int $priority, callable $callback)
 {
	if ($mod)
	{
		$found = 0; // keep track of whether the module was loaded
		foreach (Module::$modules as $m) // $m = Module in the list
			if ($m == $mod)
				$found = 1;

		if (!$found) {
			DebugLog("Could not find module $mod->name, hook for type $hook not added.");
			return false;
		}
	}

	$h = (object)[];
	$h->type = $hook;
	$h->modname = $mod->name ?? NULL;
	$h->priority = $priority;
	$h->callback = $callback;

	if (!empty(Hooks::$list[$h->type]))
	{
		$i = 0;
		foreach(Hooks::$list[$h->type] as $lhook)
		{
			if ($lhook->priority > $h->priority)
			{
				array_splice(Hooks::$list[$h->type], $i, 0, $h);
				break;
			}
			$i++;
		}
	}
	else
		Hooks::$list[$h->type][] = $h;

	return true;
}

/**
 * HOOK_DENY
 * Deny the hook
 */
define('HOOK_DENY', -1);
/**
 * HOOK_ALWAYS_DENY
 * Deny the hook even if the user has override
 */
define('HOOK_ALWAYS_DENY', -2);
/**
 * HOOK_CONTINUE
 * Continue onto the next hook
 */
define('HOOK_CONTINUE', 0);
/**
 *  HOOK_ALLOW
 * Allow the hook, stop processing further
 */
define('HOOK_ALLOW', 1);

/**
 * Runs the hook with the specified parameters
 * @param string $hook
 * @param array[] $params
 * @return void
 */
function RunHook(String $hook, Array &...$params) : void
{
	if (!isset(Hooks::$list[$hook]) || !is_array(Hooks::$list[$hook]))
		DebugLog("Attempted to run non-existent/unregistered hook: $hook.");

	else foreach(Hooks::$list[$hook] as $h)
			call_user_func_array($h->callback, $params);
}

/**
 * Hooks 
 */
class Hooks
 {
	public static $list = [];
	
 }