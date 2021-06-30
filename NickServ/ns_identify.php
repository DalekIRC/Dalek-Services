<?php
/*				
//	(C) 2021 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: Identify
//	
\\	Desc: Provides commands "identify", "id" and "login"
//	to identify to a services account.
\\	
//	Syntax: <identify|id|login> [account] <password>
\\	
//	
\\	Version: 1
//				
\\	Author:	Valware
//				
*/


//nickserv privmsg hook		 declare func with our incoming hook array
nickserv::func("privmsg",	 function($u){
	
	// our global for bot $ns and config $nickserv
	global $ns,$nickserv;
	
	$nick = find_person($u['nick']); // find 'em
	
	$parv = explode(" ",$u['msg']); // splittem
	
	if ($nickserv['login_method'] !== "default"){ return; }	// default config option
	
	if (strtolower($parv[0]) !== "identify" && strtolower($parv[0]) !== "login" && strtolower($parv[0]) !== "id"){ return; } // our command
	
	if (!isset($parv[1])){ $ns->notice($nick['UID'],"Syntax: /msg $ns->nick identify [account] <password>"); return; }
	
	// user is logging into account for their nick or notice
	$account = (isset($parv[2])) ? $parv[1] : $nick['nick'];
	$pass = (isset($parv[2])) ? $parv[2] : $parv[1];
	
	
	if (!df_verify_userpass($account,$pass)){ $ns->notice($nick['UID'],"Could not verify identity."); return; }
	
	
	$ns->sendraw(":$ns->nick SVSLOGIN * ".$nick['UID']." $account 0");
	$ns->sendraw(":$ns->nick SVS2MODE ".$nick['UID']." +r");
	$ns->notice($nick['UID'],"You are now logged into account $account");
	
});


function df_verify_userpass($user,$pass){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return "ERROR"; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_accounts WHERE display = ?");
		$prep->bind_param("s",$user);
		$prep->execute();
		
		$sResult = $prep->get_result();

		if ($sResult->num_rows == 0 || !isset($sResult)){ $prep->close(); return false; }
		
		// pre-fail this lol
		$result = false;
		
		while ($row = $sResult->fetch_assoc()){
			
			echo $pass."->".$row['pass'];
			
			if (password_verify($pass,$row['pass'])){ $result = true; }
		}
		
		$prep->close();
		return $result;
	}
}