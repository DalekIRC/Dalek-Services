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
\\	Desc: Allows RPC callers to get information about users on IRC
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class rpc_channel {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "rpc_channel";
	public $description = "Provides RPC lookups for channels";
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

		RPCHandlerAdd($this->name, "channel.list", 'rpc_channel::list', $err);
		RPCHandlerAdd($this->name, "channel.get", 'rpc_channel::get', $err);

		if ($err)
			return die(SVSLog($err));

		return true;
	}
	
	function list($id, $params)
	{
		$reply = rpc_new_reply();
		rpc_append_result($reply, channel_list());
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
	}
	function get($id, $params)
	{
		if (BadPtr($params['channel']))
			rpc_append_error($reply, "'channel' param was not specified.", RPC_ERR_INVALID_PARAMS);
		
		elseif (!($user = new Channel($params['channel']))->IsChan)
			rpc_append_error($reply, "Channel not found", RPC_ERR_NOT_FOUND);

		else
			rpc_append_result($reply, $user);

		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
	}
}