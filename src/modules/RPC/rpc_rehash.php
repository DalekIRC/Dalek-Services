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

require_module("rehash");

class rpc_rehash {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "rpc_rehash";
	public $description = "Provides RPC rehash of servers";
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

		RPCHandlerAdd($this->name, "rehash", 'rpc_rehash::rehash', $err);

		if ($err)
			return die(SVSLog($err));

		return true;
	}
	function rehash($id, $params)
	{
		if (BadPtr($params['server']))
		{
			rpc_append_error($reply, "'server' param was not specified.", RPC_ERR_INVALID_PARAMS);
			SVSLog("Could not rehash: Server parameter was not specified", LOG_RPC);
		}
		elseif (!($user = new User($params['server']))->IsServer)
		{
			rpc_append_error($reply, "Server not found", RPC_ERR_NOT_FOUND);
			SVSLog("Could not rehash: Server not found", LOG_RPC);
		}
		else
		{
			$error = "";

			if (IsMe($user->nick))  // if it's for us
			{
				$bool = do_rehash($error);

				if (!$bool)
				{
					SVSLog("Could not rehash: $error", LOG_RPC);
					rpc_append_error($reply, $error, RPC_INTERNAL_ERROR);
				}
				else
				{
					rpc_append_result($reply, "Success");
					SVSLog("Rehashed successfully", LOG_RPC);
				}
			}
			else // otherwise just propogate and say we sent it, don't confirm for unrealircd at this time
			{
				SVSLog("Sent REHASH to $user->nick", LOG_RPC);
				S2S("REHASH $user->nick");
				rpc_append_result($reply, "Success");
			}
		}
		
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
	}
}