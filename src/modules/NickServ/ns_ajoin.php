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
\\	Title: Ajoin
//	
\\	Desc: Auto-join on identify.
//	Allows you to add/remove to a list of channels you wish to
\\	be autojoined to when you identify with NickServ.
//	
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/
class ns_ajoin {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ns_ajoin";
	public $description = "NickServ AJOIN Command";
	public $author = "Valware";
	public $version = "1.0";
<<<<<<< HEAD
	public $official = true;
=======
    public $official = true;
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		$conn = sqlnew();
		$conn->query("CREATE TABLE IF NOT EXISTS dalek_ajoin (
			id INT AUTO_INCREMENT NOT NULL,
			account VARCHAR(255) NOT NULL,
			channel VARCHAR(255) NOT NULL,
			PRIMARY KEY (id)
		)");
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
<<<<<<< HEAD
		hook::del("auth", 'ns_ajoin::hook_do_ajoin');
=======
		
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
<<<<<<< HEAD
		$help_string = "View and change your auto-join list";
		$syntax = "AJOIN [ADD|DEL|LIST] [<#channel>]";
		$extended_help = 	"$help_string\n$syntax";

		if (!AddServCmd(
			'ns_ajoin', /* Module name */
			'NickServ', /* Client name */
			'AJOIN', /* Command */
			'ns_ajoin::cmd_ajoin', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;
		hook::func("auth", 'ns_ajoin::hook_do_ajoin');
=======
		nickserv::func("privmsg", 'ns_ajoin::cmd_ajoin');
		nickserv::func("identify", 'ns_ajoin::hook_ident');
		nickserv::func("saslconf", 'ns_ajoin::hook_sasl');
		nickserv::func("helplist", 'ns_ajoin::helplist');
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_ajoin($u)
	{
<<<<<<< HEAD
		$ns = $u['target'];
		
		$nick = $u['nick'];
=======
		global $ns;
	
		$nick = new User($u['nick']);
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
		$account = $nick->account ?? NULL;
		$parv = explode(" ",$u['msg']);
		$cmd = $parv[0] ?? NULL;
		$flag = (isset($parv[1])) ? strtolower($parv[1]) : NULL;
		
		if ($cmd !== "ajoin") { return; }

		if (!$account){ $ns->notice($nick->nick,IRC("ERR_NOTLOGGEDIN")); return; }
		if (!$flag){ goto badsyntax_ajoin; }
		if ($flag !== "add" && $flag !== "del" && $flag !== "list"){ goto badsyntax_ajoin; }
		
		if ($flag == "add"){
			if (!($channel = find_channel($parv[2]))){ $ns->notice($nick->nick,"Channel does not exist"); return; }
<<<<<<< HEAD
			if (($reply = ns_ajoin::ajoin_add($account,$channel['channel'])) !== true){ $ns->notice($nick->nick,$reply); return; }
=======
			if (($reply = ajoin_add($account,$channel['channel'])) !== true){ $ns->notice($nick->nick,$reply); return; }
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
			$ns->notice($nick->nick,$reply);
			$ns->log($nick->nick." added ".$channel['channel']." to the ajoin list for account $account");
			return;
		}
		elseif ($flag == "del"){
			$channel = $parv[2];
<<<<<<< HEAD
			if (($reply = ns_ajoin::ajoin_del($account,$channel)) !== true){ $ns->notice($nick->nick,$reply); return ;}
=======
			if (($reply = ajoin_del($account,$channel)) !== true){ $ns->notice($nick->nick,$reply); return ;}
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
			$ns->log($nick->nick." deleted $channel from the ajoin list for account $account");
			$ns->notice($nick->nick,$reply);
			return;
		}
		elseif ($flag == "list"){
<<<<<<< HEAD
			if (!($list = ns_ajoin::ajoin_list($account))){ $ns->notice($nick->nick,"Your autojoin list is empty."); return; }
			$ns->notice($nick->nick,"Listing your autojoin list:");
			foreach($list as $chan){
				$ns->notice($nick->nick,$chan);
=======
			if (!($list = ajoin_list($account))){ $ns->notice($nick->nick,"Your autojoin list is empty."); return; }
			$ns->notice($nick->nick,"Listing your autojoin list:");
			while($row = $list->fetch_assoc()){
				$ns->notice($nick->nick,$row['channel']);
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
			}
			return;
		}
		
		badsyntax_ajoin:
		$ns->notice($nick->nick,"Syntax: AJOIN <[add|del]|list> [<channel>]");
		return;
	}
<<<<<<< HEAD
	
	public static function hook_do_ajoin($u)
	{
		global $nickserv;
		$user = new User($u['uid']);
		$ns = Client::find($nickserv['nick']);
		if (!($list = ns_ajoin::ajoin_list($u['account'] ?? NULL))){ return; }
		foreach($list as $chan)
			$ns->sendraw("SVSJOIN $user->nick $chan");
	}
	
	function IsAjoin($account,$channel){
		
		$list = ns_ajoin::ajoin_list($account) ?? NULL;
		
		if (!$list){ return; }
		foreach ($list as $chan)
			if ($chan == $channel)
				return true;
		return false;
	}
=======
	public static function hook_ident($u)
	{
		global $ns,$cf;
	
		if (!($list = ajoin_list($u['nick']->account ?? NULL))){ return; }
		while($row = $list->fetch_assoc())
			if (isset($row['channel'])){ $ns->sendraw(":".$cf['sid']." SVSJOIN ".$u['nick']->nick." ".$row['channel']); }
	}
	public static function hook_sasl($u)
	{
		global $ns,$cf;
		$user = new User($u['uid']);
		if (!($list = ajoin_list($u['account'] ?? NULL))){ return; }
		while($row = $list->fetch_assoc())
			if (isset($row['channel'])){ $ns->sendraw(":".$cf['sid']." SVSJOIN ".$user->nick." ".$row['channel']); }
	}
	public static function helplist($u)
	{
		global $ns;
		
		$nick = $u['nick'];
		
		$ns->notice($nick,"AJOIN               ".IRC("HELPCMD_AJOIN"));
	}
}

if (!function_exists('IsAjoin'))
{
	function IsAjoin($account,$channel){
		
		$list = ajoin_list($account) ?? NULL;
		$return = NULL;
		if (!$list){ return; }
		if ($list->num_rows == 0){ return; }
		while($row = $list->fetch_assoc()){
			if ($row['channel'] == $channel){ $return = true; }
		}
		if ($return){ return $return; }
		else { return false; }
	}
}
if (!function_exists('ajoin_list'))
{
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
	function ajoin_list($account){
		
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {
			$prep = $conn->prepare("SELECT channel FROM dalek_ajoin WHERE account = ?");
			$prep->bind_param("s",$account);
			$prep->execute();
			$sResult = $prep->get_result();
<<<<<<< HEAD
			if (!$sResult || $sResult->num_rows == 0)
				return false;

			$chans = [];
			while($row = $sResult->fetch_assoc())
				$chans[] = $row['channel'];

			$prep->close();
			return $chans;
		}
	}
=======
			$yep = $sResult;
			if ($sResult->num_rows == 0){ return false; }
			
			$prep->close();
			return $yep;
		}
	}
}
if(!function_exists('ajoin_add'))
{
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
	function ajoin_add($account,$channel){
		
		$conn = sqlnew();
		if (!$conn) { return false; }
		
<<<<<<< HEAD
		if (ns_ajoin::IsAjoin($account,$channel))
			return "That channel is already on your list.";
=======
		if (IsAjoin($account,$channel)){ return "That channel is already on your list."; return; }
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
		
		else {
			$prep = $conn->prepare("INSERT INTO dalek_ajoin (account, channel) VALUES (?, ?)");
			$prep->bind_param("ss",$account,$channel);
			$prep->execute();
			return "$channel has been added to your autojoin list";
		}
	}
<<<<<<< HEAD
=======
}
if (!function_exists('ajoin_del'))
{
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
	function ajoin_del($account,$channel)
	{		
		$conn = sqlnew();
		if (!$conn) { return false; }
		
<<<<<<< HEAD
		if (!ns_ajoin::IsAjoin($account,$channel)){ return "That channel is not on your list."; return; }
=======
		if (!IsAjoin($account,$channel)){ return "That channel is not on your list."; return; }
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
		
		else {
			$prep = $conn->prepare("DELETE FROM dalek_ajoin WHERE account = ? AND channel = ?");
			$prep->bind_param("ss",$account,$channel);
			$prep->execute();
			return "$channel has been deleted from your autojoin list";
		}
	}
}
