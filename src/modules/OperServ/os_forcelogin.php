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
\\	Title: FORCELOGIN
//	
\\	Desc: Forces a user to login as a specific account
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class os_forcelogin {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "os_forcelogin";
	public $description = "Forces a user to login as a specific account";
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
		$cmd = "FORCELOGIN";
		$help_string = "Forces a user to login as a specific account.";
		$syntax = "$cmd <nick> <account>";
		$extended_help = 	"Forces a user to login as a specific account.\n/msg OperServ $syntax\n \nUse '0' as an account name to log the user out.";

		if (!AddServCmd(
			'os_forcelogin', /* Module name */
			'OperServ', /* Client name */
			$cmd, /* Command */
			'os_forcelogin::cmd', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		$err = NULL;
		if (!RPCHandlerAdd(
			'os_forcelogin',
			'operserv.forcelogin',
			'os_forcelogin::rpc_cmd',
			$err
		)) return die(SVSLog($err));

		return true;
	}
	
	function cmd($u)
	{
		$parv = explode(" ",$u['msg']);
		$os = $u['target'];
		$nick = $u['nick'];

		if (!ValidatePermissionsForPath("can_forcelogin", $nick))
		{
			$os->notice($nick->uid,"Permission denied!");
			return;
		}

		if (count($parv) < 3)
		{
			$os->notice($nick->uid,"Invalid parameters.");
			return;
		}

		$user = new User($parv[1]);

		$account = new WPUser($parv[2], LOOKUP_BY_ACCOUNT_NAME);

		if (!$user->IsUser)
		{
			$os->notice($nick->uid,"The user you specified is not online");
			return;
		}

		if (!$account->IsUser)
		{
			$os->notice($nick->uid,"The account you specified does not exist.");
			return;
		}
		
		svslogin($user->uid, $account->user_login);
		
		/* I feel like NickServ should convey the message */
		Client::find("NickServ")->notice($nick->uid,"You have been logged in with account name '$account->user_login'");
		
		SVSLog("$nick->nick ($nick->ident@$nick->realhost) used FORCELOGIN to forcefully login $user->nick to account $account->user_login");

	}
	function rpc_cmd($id, $params)
	{
		$reply = rpc_new_reply();
		if (count($params) != 2)
			rpc_append_error($reply, "You may only specify one target", RPC_ERR_INVALID_PARAMS);

		elseif (!isset($params['user']) || !isset($params['account']))
			rpc_append_error($reply, "Requests expects params 'user' and 'account'", RPC_ERR_INVALID_PARAMS);

		else {
			$user = new User($params['user']);
			$wp = new WPUser($params['account']);
					
			if (!$user->IsUser)
				rpc_append_error($reply, "That user is not online", RPC_ERR_INVALID_REQUEST);
			
			elseif (!$wp->IsUser && $params['account'] != 0)
					rpc_append_error($reply, "That account does not exist", RPC_ERR_INVALID_REQUEST);

			else
			{

				SVSLog("[RPC] Logging in $user->nick into account '$wp->user_login'");
				svslogin($user->uid, $wp->user_login);
				rpc_append_result($reply, "User \"$user->nick\" logged into account \"$wp->user_login\" successfully.");
			}
		}
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
	}
}
