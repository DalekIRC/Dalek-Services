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
\\	Title: Set Email
//	
\\	Desc: Allows a user to update the email address associated
//	with their account.
\\	
//	Syntax: SET EMAIL <new email>
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/



nickserv::func("setcmd", function($u){
	
	global $ns,$nickserv;
	$nick = new User($u['nick']);
	$parv = explode(" ",$u['cmd']);
	if ($parv[0] !== "set"){ return; }

	
	if ($parv[1] !== "email"){ return; }
	if (!($account = IsLoggedIn($u['nick']))){ $ns->notice($u['UID'],"You must be logged in to use this command."); return; }
	if ($nickserv['login_method'] !== "default"){ return; }
	if (!isset($parv[2])){ return; }
	
	$email = $parv[2];
	$account = df_AccountDetails($account);
	

	if ($account['email'] == $email){ $ns->notice($nick->uid,"That is already your email address."); return; }
	
	if (!validate_email($email)){ $ns->notice($nick->uid,IRC("ERR_BADEMAIL")); return ; }
	
	if (!df_UpdateEmail($account['display'],$email)){ $ns->notice($nick->uid,"An error occurred."); return; }
	
	$ns->log($nick->nick." (account: ".$account['display'].") has updated their email address to be $email");
	$ns->notice($nick->uid,"Your email has been updated to be $email");
	return;
	
	
});

function df_UpdateEmail($account,$email){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("UPDATE dalek_accounts SET email = ? WHERE display = ?");
		$prep->bind_param("ss",$email,$account);
		$prep->execute();
		$prep->close();
		return true;
	}
	return false;
}
nickserv::func("setlist", function($u){
	
	global $ns,$nickserv;
	if ($nickserv['login_method'] !== "default")
		return;
	if (isset($u['key'])){ return; }
	if (isset($parv[0])){ return; }
	$ns->notice($u['nick'],"EMAIL               Set the email address associated with your account.");
});
