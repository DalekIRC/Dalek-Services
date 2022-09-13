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


/* Hooktype Definitions */
/** HOOKTYPE_RAW:
 * @param = array("uid", "dest", "parv")
 * "uid" = The UID (or SID) of the caller
 * "dest" = The raw destination 
 */
define('HOOKTYPE_RAW', 'raw');
define('HOOKTYPE_NEW_MESSAGE', 'privmsg'); /* When a new message is sent, regardless of if it's a channel or user message or notice*/
define('HOOKTYPE_USER_MESSAGE', 'usermsg'); /* When a new message is sent to a pseudoclient on our server */
define('HOOKTYPE_TAGMSG', 'tagmsg');
define('HOOKTYPE_CHANNEL_MESSAGE', 'chanmsg'); /* When a new message is sent to a channel we can see the messages for */
define('HOOKTYPE_PRE_CONNECT', 'preconnect'); /* Before our server attempts to open a connection to the IRCd */
define('HOOKTYPE_CONNECT', 'connect'); /* When we have successfully opened our socket, before we have finished syncing with the server */
define('HOOKTYPE_START', 'start'); /* When we have successfully finished syncing with the server and we are good to go. */
define('HOOKTYPE_NOTICE', 'notice'); /* When a notice gets received from a user */
define('HOOKTYPE_JOIN', 'join'); /* When a user joins a channel */
define('HOOKTYPE_PART', 'part'); /* When a user parts a channel */
define('HOOKTYPE_QUIT', 'quit'); /* When a user disconnects from the network */
define('HOOKTYPE_CTCP', 'ctcp'); /* When a CTCP is received */
define('HOOKTYPE_CTCPREPLY', 'ctcpreply'); /* When we recieve a CTCP reply */
define('HOOKTYPE_CHANNELMODE', 'mode'); /* When one or more channel modes were set */
define('HOOKTYPE_UMODE_CHANGE', 'umode'); /* When one or more usermodes were set */
define('HOOKTYPE_KICK', 'kick'); /* When a user gets kicked from a channel */
define('HOOKTYPE_TKL', 'tkl'); /* When we hear about a TKL */
define('HOOKTYPE_AUTHENTICATE', 'auth'); /* When a user auths with services */
define('HOOKTYPE_SASL_CONTINUATION', 'sasl_cont'); /* A continuation from a SASL we already dealt with */
define('HOOKTYPE_SASL_RESULT', 'sasl_result'); /* Handle what happens after the result of their SASL has been processed */
define('HOOKTYPE_PING', 'ping'); /* When we get a ping. This is usually used for piggybacking a regular timed event */
define('HOOKTYPE_WELCOME', 'UID'); /* When we have a new user (local or remote) */
define('HOOKTYPE_SERVER_CONNECT', 'SID'); /* When a new server is introduced to us */
define('HOOKTYPE_SJOIN', 'SJOIN'); /* When we are being told about the entire contents of a channel (users)*/
define('HOOKTYPE_CHANNEL_CREATE', 'newchan'); /* When a channel is created */
define('HOOKTYPE_CHANNEL_DESTROY', 'destroychan'); /* When a channel is destroyed */
define('HOOKTYPE_RPC_CALL', 'rpc_call'); // for RPC calls (not for IRC)
define('HOOKTYPE_USERMODE', 'umode'); // for usermode changes
define('HOOKTYPE_METADATA', 'usermeta'); // metadata hook
define('HOOKTYPE_UPDATE_FOUND', 'update_found'); // for dalek updates
define('HOOKTYPE_EOS', 'eos'); // end of sync
define('HOOKTYPE_REHASH', 'rehash'); // when Dalek has been rehashed
define('HOOKTYPE_USER_MD', 'usermd');
define('HOOKTYPE_CHANNEL_MD', 'chanmd');





class hook {

	private static $actions = [];

	public static function run($hook, &$args = array())
	{
		if (!empty(self::$actions[$hook]))
			foreach (self::$actions[$hook] as &$f)
				$f($args);
			
	}

	public static function func($hook, $function) {
		self::$actions[$hook][] = $function;
	}
	public static function del($hook, $function) {
		for ($i = 0; isset(self::$actions[$hook][$i]); $i++)
		  if (self::$actions[$hook][$i] == $function)
		  array_splice(self::$actions[$hook],$i);
	}
}

