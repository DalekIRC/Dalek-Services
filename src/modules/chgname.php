<?php
/*				
//	(C) 2022 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title:		CHGNAME
//				
\\	Desc:		CHGNAME command
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

/* class name needs to be the same name as the file */
class chgname {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "chgname";
	public $description = "Provides CHGNAME command";
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
		
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		/* Params: CommandAdd( this module name, command keyword, function, parameter count)
		 * the function is a string reference to this class, the cmd_elmer method (function)
		 * The last param is expected parameter count for the command
		 * (both point to the same function which determines)
        */

		if (!CommandAdd($this->name, 'CHGNAME', 'chgname::cmd_chgname', 1))
			return false;

		if (!RPCHandlerAdd($this->name, 'chg.name', 'chgname::rpc_cmd', $err))
			return false;

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
     * $u['params'] = Parameters
     * $u['cmd'] = calling command if needed
	 */
	public static function cmd_chgname($u)
	{
        $parv = explode(" :",$u['params']);
        $gecos = cut_first_from($u['params']);
        $uid = $parv[0];
		$conn = sqlnew();
		$prep = $conn->prepare("UPDATE dalek_user SET gecos = ? WHERE UID = ?");
		$prep->bind_param("ss",$gecos,$uid);
		$prep->execute();
    }


	public static function rpc_cmd($id, $params)
	{
		
		$reply = rpc_new_reply();
		if (!isset($params['user']) || !isset($params['name']))
		{
			rpc_append_error($reply, "Request expects params 'user' and 'name'", RPC_ERR_INVALID_PARAMS);
			rpc_append_id($reply, $id);
			rpc_send_reply($id, $reply);
			return;
		}
		$err = 0;

		$user = new User($params['user']);
		if (!$user->IsUser)
		{
			$err++;
			rpc_append_error($reply, "That user is not online", RPC_ERR_INVALID_REQUEST);
		}
		
		/* sigh check if the username already exists */

		$target = new User($params['user']);
		if ($target->IsUser)
		{
			$err++;
			rpc_append_error($reply, "That username already exists. Sorry!", RPC_ERR_ALREADY_EXISTS);
		}

		if ($err > 0)
		{
			rpc_append_id($reply, $id);
			rpc_send_reply($id, $reply);
			return;
		}
		

		/* now we can probably pass it back through that thing over there */
		$u['params'] = $user->uid." ".$params['name'];
		self::cmd_chgname($u);

		/* send it */
		S2S("CHGNAME $user->uid ".$params['name']);

		/* log it */

		SVSLog("Changed the 'real name' of $user->nick ($user->ident@$user->realhost) to be: ".$params['name'], LOG_RPC);

		/* return info about it to the RPC caller */
		rpc_append_result($reply, "Success");
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
	}
}
