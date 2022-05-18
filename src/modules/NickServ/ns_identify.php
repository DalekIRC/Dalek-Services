<?php
<<<<<<< HEAD

=======
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
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
<<<<<<< HEAD
\\	Title: Logout
//	
\\	Desc: Log yourself out of NickServ
//	
\\	
//	
=======
\\	Title: Identify
//	
\\	Desc: Provides commands "identify", "id" and "login"
//	to identify to a services account.
\\	
//	Syntax: <identify|id|login> [account] <password>
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/
<<<<<<< HEAD
require_module("SASL");

class ns_identify {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ns_identify";
	public $description = "NickServ IDENTIFY command to trigger SASL";
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
		$cmd = "IDENTIFY";
		$help_string = "Initiates a request to identify using SASL";
		$syntax = "$cmd [plain|external]";
		$extended_help = 	"$help_string\n".
							"Note: This command does not accept a password.\n".
							"Instead, it asks your client to start performing\n".
							"a SASL. So please make sure you have setup your\n".
							"password in your client.\n".
							"$syntax";

		if (!AddServCmd(
			'ns_identify', /* Module name */
			'NickServ', /* Client name */
			$cmd, /* Command */
			'ns_identify::cmd', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;
		
		return true;
	}

	
	public static function cmd($u) : void
	{
		global $_SASL;
		$ns = $u['target'];
		$nick = $u['nick'];
		$parv = explode(" ",$u['msg']);
			
		
		if (isset($parv[1])) // Let 'em know for future reference
		{
			if (strcasecmp($parv[1],"plain") && strcasecmp($parv[1],"external"))
			{
				$ns->notice(
					$nick->uid,
					"It looks like you provided a password. It has been ignored.",
					"For more information, type '/msg $ns->nick HELP IDENTIFY'"
				);
				return;
			}

			$mech = strtoupper($parv[1]);
		}
		else // just assume they're doing external really
			$mech = "EXTERNAL";

		$extra = ($mech == "EXTERNAL") ? $nick->meta->certfp : "";
		$s = ($mech == "EXTERNAL") ? "S" : "C";
		$sasl = new IRC_SASL($nick->server,$nick->uid,"H",$nick->ip,$nick->ip);
		$sasl = new IRC_SASL($nick->server,$nick->uid,$s,$mech,$extra);
		
	}
}
=======


//nickserv privmsg hook		 declare func with our incoming hook array
nickserv::func("privmsg",	 function($u){
	
	// our global for bot $ns and config $nickserv
	global $ns,$nickserv;
	
	$nick = new User($u['nick']); // find 'em
	
	$parv = explode(" ",$u['msg']); // splittem
	
	if ($nickserv['login_method'] !== "default"){ return; }	// default config option
	
	if (strtolower($parv[0]) !== "identify" && strtolower($parv[0]) !== "login" && strtolower($parv[0]) !== "id"){ return; } // our command
	
	if (!isset($parv[1])){ $ns->notice($nick->uid,IRC("MSG_IDENTIFY_SYNTAX")); return; }
	
	// user is logging into account for their nick or notice
	if (isset($parv[2])){
		$account = $parv[1];
		$pass = $parv[2];
	}
	else {
		$account = $nick->nick;
		$pass = $parv[1];
	}
	
	if (!df_verify_userpass($account,$pass)){ $ns->notice($nick->uid,IRC("MSG_IDENTIFAIL")); return; }
	
	if (!df_login($nick->uid,$account)){
		
		//account writing failed for some reason, return;
		$ns->log(IRC("LOG_IDENTIFAIL"));
		$ns->notice($nick->uid,IRC("ERR_IDENTIFAIL"));
		return;
	}
	$ns->log($nick->nick." (".$nick->uid.") ".IRC("LOG_IDENTIFY")." $account"); 
	$ns->svslogin($nick->uid,$account);
	$ns->svs2mode($nick->uid," +r");
	$ns->notice($nick->uid,IRC("MSG_IDENTIFY")." $account");
	
	nickserv::run("identify", array('nick' => $nick, 'account' => $account));
	
});


function df_verify_userpass($user,$pass){
	
	$conn = sqlnew();
	if (!$conn) { return "ERROR"; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_accounts WHERE display = ?");
		$prep->bind_param("s",$user);
		$prep->execute();
		
		$sResult = $prep->get_result();

		if ($sResult->num_rows == 0 || !isset($sResult)){ $prep->close(); return false; }
		
	
		$result = false;
		
		while ($row = $sResult->fetch_assoc()){
			
			if (password_verify($pass,$row['pass'])){ $result = true; }
		}
		
		$prep->close();
		return $result;
	}
}


function df_login($nick,$account){
	
	$conn = sqlnew();
	if (!$conn) { return "ERROR"; }
	else {
		
		// lmao
		$nick = new User($nick);
		$nick = $nick->uid;
		
		$prep = $conn->prepare("UPDATE dalek_user SET account = ? WHERE UID = ?");
		$prep->bind_param("ss",$account,$nick);
		$prep->execute();
		$prep->close();
		$prep = $conn->prepare("SELECT account FROM dalek_user WHERE UID = ?");
		$prep->bind_param("s",$nick);
		$prep->execute();
		$sResult = $prep->get_result();
		
		$result = false;
		
		while ($row = $sResult->fetch_assoc()){
			
			if (!$row['account'] || $row['account'] != $account){ $result = false; }
			else { $result = true; }
		}
		$prep->close();
	
		return $result;
	}
}

function IsLoggedIn($nick){
	
	global $sql;
	
	$person = new User($nick);
	if (!$person->IsUser){ return false; }
	
	$uid = $person->uid;
	
	$query = "SELECT account FROM dalek_user WHERE UID = '$uid'";
	$result = $sql::query($query);
	
	if (mysqli_num_rows($result) == 0){ return false; }
	
	$row = mysqli_fetch_assoc($result);
	$account = $row['account'];
	mysqli_free_result($result);
	return $account;
}
	

nickserv::func("helplist", function($u){
	
	global $ns;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"IDENTIFY            ".IRC("HELPCMD_IDENTIFY"));
	
});



nickserv::func("help", function($u){
	
	global $ns;
	
	if ($u['key'] !== "identify"){ return; }
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"Command: IDENTIFY");
	$ns->notice($nick,"Syntax: /msg $ns->nick identify [account] password");
	$ns->notice($nick,"Example: /msg $ns->nick identify Sup3r-S3cur3");
});



hook::func("UID", function($u)
{
	global $ns;
	if (!isset($u['account']))
		return;
	if ($u['account'] == $u['nick'])
		$ns->sendraw(":$ns->nick SVS2MODE ".$u['nick']." +r");
});
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
