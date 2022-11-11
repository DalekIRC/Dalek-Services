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
\\	Title: RPC 'SVSNOOP' functions
//	
\\	Desc: Allows RPC callers to disable opers on a particular server
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class rpc_noop {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	const NOOP_ADD = 1;
	const NOOP_DEL = 0;
	const NOOP_ISON = -1;
	public $name = "rpc_noop";
	public $description = "Provides RPC lookups for users";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		
		sqlnew()->query("CREATE TABLE IF NOT EXISTS ".sqlprefix()."noop (
						id int AUTO_INCREMENT NOT NULL,
						server varchar(255) NOT NULL,
						PRIMARY KEY(id)
					)");
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

		RPCHandlerAdd($this->name, "noop.list", 'rpc_noop::list', $err);
		RPCHandlerAdd($this->name, "noop.set", 'rpc_noop::get', $err);
		RPCHandlerAdd($this->name, "noop.del", 'rpc_noop::del', $err);

		if ($err)
			return die(SVSLog($err));

		return true;
	}
	
	function list($id, $params)
	{
		$reply = rpc_new_reply();
		rpc_append_result($reply, user_list());
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
	}
	function set($id, $params)
	{
		$reply = rpc_new_reply();
		if (BadPtr($params['server']))
			rpc_append_error($reply, "'server' param was not specified.", RPC_ERR_INVALID_PARAMS);
		
		elseif (!($user = new User($params['nick']))->IsServer)
			rpc_append_error($reply, "Server not found", RPC_ERR_NOT_FOUND);

		elseif (self::sql($user->nick, self::NOOP_ISON))
			rpc_append_error($reply, "That server is already No-Op'd", RPC_ERR_ALREADY_EXISTS);

		else
		{
			S2S("NOOP %user->nick +");
			self::sql($user->nick, self::NOOP_ADD);
			rpc_append_result($reply, "Success");
		}
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
	}
	/**
	 * SQL Funcs
	 * @param server The server name
	 * @param option int of 1 or 0, 1=add 0=delete -1=lookup
	 */
	static function sql($server, $option)
	{
		$conn = sqlnew();
		if ($option == 1)
			$prep = $conn->prepare("INSERT INTO ".sqlprefix()."noop (server) VALUE (?)");
			
		elseif ($option == 0)
			$prep = $conn->prepare("DELETE FROM ".sqlprefix()."noop WHERE server = ?");

		elseif ($option == -1) // do we have?
			$prep = $conn->prepare("SELECT FROM ".sqlprefix()."noop WHERE server = ?");
		

		$prep->bind_param("s",$server);
		$prep->execute();

		if ($option !== -1)
			return;

		$result = $prep->get_result();
		if (!$result || !$result->num_rows)
			return false;
		return true;
		
	}
	static function noop_list()
	{
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT FROM ".sqlprefix()."noop WHERE server = ?");
	}
}