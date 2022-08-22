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
\\	Title: RPC User functions
//	
\\	Desc: Allows RPC callers to change a users nick on IRC
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class rpc_nick {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "rpc_nick";
	public $description = "Allows changing a nick over IRC";
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

		RPCHandlerAdd($this->name, "chg.nick", 'rpc_nick::change', $err);

		if ($err)
			return die(SVSLog($err));

		return true;
	}
	function change($id, $params)
	{
		$reply = rpc_new_reply();
		if (BadPtr($params['nick']))
			rpc_append_error($reply, "'nick' param was not specified.", RPC_ERR_INVALID_PARAMS);
		
		elseif (BadPtr($params['newnick']))
			rpc_append_error($reply, "'newnick' param was not specified.", RPC_ERR_INVALID_PARAMS);

		elseif (!($user = new User($params['nick']))->IsUser)
			rpc_append_error($reply, "User not found \"".$params['nick']."\"", RPC_ERR_NOT_FOUND);

		elseif (($new = new User($params['newnick']))->IsUser)
			rpc_append_error($reply, "That user already exists", RPC_ERR_ALREADY_EXISTS);

		else
		{
			$user->NewNick($params['newnick']);
			rpc_append_result($reply, "\"".$params['nick']."\" is now known as \"".$params['newnick']."\"");
		}
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
	}
}