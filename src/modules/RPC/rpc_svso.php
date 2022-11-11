<?php

/*				
//	(C) 2021 DalekIRC Services
\\				
//			dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: RPC Rehash
//	
\\	Desc: Allows RPC callers to rehash specific servers
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/

require_module("svso");

class rpc_svso {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "rpc_svso";
	public $description = "Lets a web-user oper someone over RPC";
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
		/* We automatically clear up things attached to the module information, like AddServCmd();
		 * so don't worry!
		*/
	}

	function __init()
	{
		$err = NULL;

		RPCHandlerAdd($this->name, "svso.add", 'rpc_svso::add', $err);
		RPCHandlerAdd($this->name, "svso.del", 'rpc_svso::del', $err);

		if ($err)
			return die(SVSLog($err, LOG_RPC) && DebugLog($err));

		return true;
	}

	/**
	 * SVSO <uid|nick> <oper account> <operclass> [<class> <modes> <snomask> <vhost>]
	 */
	static function add($id, $params)
	{
		$reply = rpc_new_reply();
		if (BadPtr($params['user']))
		{
			rpc_append_error($reply, "'user' param was not specified.", RPC_ERR_INVALID_PARAMS);
			SVSLog("[SVSO]: User parameter was not specified", LOG_RPC);
		}
		elseif (!($user = new User($params['user']))->IsUser)
		{
			rpc_append_error($reply, "User not found", RPC_ERR_NOT_FOUND);
			SVSLog("[SVSO]: User not found", LOG_RPC);
		}
		
		else
		{
			$account = $user->account ?? false;
			if (!$account)
			{
				rpc_append_error($reply, "User was not logged into an account", RPC_ERR_INTERNAL_ERROR);
				SVSLog("[SVSO]: User not logged in", LOG_RPC);
			}
			elseif (!in_array("irc_admin", $user->wp->role_array) && !in_array("irc_oper", $user->wp->role_array) && !in_array("administrator", $user->wp->role_array))
			{
				rpc_append_error($reply, "User does not have permission to be opered.", RPC_ERR_INTERNAL_ERROR);
				SVSLog("[SVSO]: User does not have permission to be opered.", LOG_RPC);
			}
			
			if (in_array("administrator",$user->wp->role_array))
			{
				$oper_class = config_get_item("opertype::administrator");
			}
			if (in_array("irc_admin",$user->wp->role_array))
			{
				$oper_class = config_get_item("opertype::irc_admin");
			}
			if (in_array("irc_oper",$user->wp->role_array))
			{
				$oper_class = config_get_item("opertype::irc_oper");
			}

			
			svso::send($user, $account, $oper_class);
			SVSLog("[SVSO.ADD] $user->nick (account: $account) (class: $oper_class)",LOG_RPC);
			rpc_append_result($reply,"Success");
		
		}
		
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
	}
	static function del($id, $params)
	{
		$reply = rpc_new_reply();
		if (BadPtr($params['user']))
		{
			rpc_append_error($reply, "'user' param was not specified.", RPC_ERR_INVALID_PARAMS);
			SVSLog("[SVSO.DEL]: User parameter was not specified", LOG_RPC);
		}
		elseif (!($user = new User($params['user']))->IsUser)
		{
			rpc_append_error($reply, "User not found", RPC_ERR_NOT_FOUND);
			SVSLog("[SVSO.DEL]: User not found", LOG_RPC);
		}
		
		else
		{
			$user->SetMode("-o");
			md::del($user,"operclass");
			md::del($user,"operlogin");
			rpc_append_result($reply,"Success");
			SVSLog("[SVSO.DEL] $user->nick (account: ".($user->account ?? "<none>") .")",LOG_RPC);
		}
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
	}
}