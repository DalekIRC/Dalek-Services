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
\\	Title:		MD
//				
\\	Desc:		MD command
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
class md {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "md";
	public $description = "Provides MD compatibility";
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

		if (!CommandAdd($this->name, 'MD', 'md::cmd_md', 0))
			return false;

		$err = NULL;
		if (!RPCHandlerAdd($this->name, 'md.get', 'md::rpc_get', $err))
			return false;

		/* we have only .get from md because of the unknown nature around remote-write permissions in the module */

		return true;
	}

	public static function send_md_client(User $user, $key, $value)
	{
		self::add($user->nick, $key, $value);
		S2S("MD client $user->uid $key :$value");
	}
	public static function send_md_channel(Channel $chan, $key, $value)
	{
		self::add($chan->name, $key, $value);
		S2S("MD channel $chan->name $key :$value");
	}
	

	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_md($u)
    {
		$parv = split($u['params']);
		$user = $parv[1];
		$key = $parv[2];
		$t = explode(" :",$u['params']);
		$value = (isset($t[1])) ? $t[1] : "";
		
		md::add($user,$key,$value);
    }
	public static function add($person,$key,$value)
	{
		$conn = sqlnew();
		if (!$conn)
			return false;


		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."user_meta WHERE UID = ? AND meta_key = ? AND meta_data = ?");
		$prep->bind_param("sss",$person,$key,$value);
		$prep->execute();
		$result = $prep->get_result();
		if (!$result || !$result->num_rows)
		{
			$prep = $conn->prepare("INSERT INTO ".sqlprefix()."user_meta (UID, meta_key, meta_data) VALUES (?, ?, ?)");
			$prep->bind_param("sss",$person,$key,$value);
		}
		else
		{
			$prep = $conn->prepare("UPDATE ".sqlprefix()."user_meta SET meta_key = ?, meta_data = ? WHERE UID = ?");
			$prep->bind_param("sss",$key,$value,$person);
		}

		$prep->execute();
		$prep->close();
	}

	/* Get a users MD
	 * Returns an array
	 */
	public static function get(User $u, $key = NULL) : array
	{
		$conn = sqlnew();
		if (!$conn)
			return false;

		$md_array = [];
		
		if (!BadPtr($key))
		{
			$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."user_meta WHERE UID = ? AND meta_key = ?");
			$prep->bind_param("ss", $u->uid, $key);
			$prep->execute();
			$result = $prep->get_result();
			if (!$result || !$result->num_rows)
				return [];

			while ($row = $result->fetch_assoc())
				$md_array[$row['meta_key']] = $row['meta_data'];
		}
		else
		{
			$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."user_meta WHERE UID = ?");
			$prep->bind_param("s", $u->uid);
			$prep->execute();
			$result = $prep->get_result();
			if (!$result || !$result->num_rows)
				return [];

			while ($row = $result->fetch_assoc())
				$md_array[$row['meta_key']] = $row['meta_data'];
		}
		if (!empty($md_array))
			return $md_array;
		return [];
	}

	public static function rpc_get($id, $params)
	{
		$reply = rpc_new_reply();
		if (!isset($params['user']))
		{
			rpc_append_error($reply, "Request expects param 'user'", RPC_ERR_INVALID_PARAMS);
			rpc_append_id($reply, $id);
			rpc_send_reply($id, $reply);
			return;
		}
		$err = 0;
		var_dump($params);
		$user = new User($params['user']);
		if (!$user->IsUser)
		{
			$err++;
			rpc_append_error($reply, "That user is not online", RPC_ERR_INVALID_REQUEST);
		}
		
		if ($err > 0)
		{
			rpc_append_id($reply, $id);
			rpc_send_reply($id, $reply);
			return;
		}
		SVSLog("Requested MD for user $user->nick ($user->ident@$user->realhost)", LOG_RPC);
		/* return info about it to the RPC caller */
		rpc_append_result($reply, md::get($user));
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
	}
}
