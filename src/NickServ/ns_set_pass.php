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
	if ($parv[0] !== "set")
		return;

	
	if ($parv[1] !== "password")
		return;
	if (!($account = $nick->account))
	{
		$ns->notice($nick->uid,"You must be logged in to use this command.");
		return;
	}
	if ($nickserv['login_method'] !== "default"){ return; }
	if (!isset($parv[4]))
	{
		$ns->notice($nick->uid,"Syntax: SET PASSWORD <old pass> <new pass> <confirm new pass>");
		return;
	}
	
	if (!df_verify_userpass($account,$parv[2]))
	{
		$ns->notice($nick->uid,IRC("MSG_IDENTIFAIL"));
		return;
	}
	if ($parv[3] !== $parv[4])
	{
		$ns->notice($nick->uid,"New passwords do not match");
		return;
	}
	$password = $parv[3];
	$password = password_hash($password, PASSWORD_DEFAULT);
	
	df_UpdatePass($nick->account,$password);
	$ns->notice($nick->uid,"You have successfully updated your password.");
	return;
	
	
});

function df_UpdatePass($account,$password){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("UPDATE dalek_accounts SET pass = ? WHERE display = ?");
		$prep->bind_param("ss",$password,$account);
		$prep->execute();
		$prep->close();
		return true;
	}
	return false;
}
nickserv::func("setlist", function($u){
	
	global $ns,$nickserv;
	
	if (isset($u['key'])){ return; }
	if (isset($parv[0])){ return; }
	if ($nickserv['login_method'] !== "default")
		return;
	$ns->notice($u['nick'],"PASSWORD            Change your password.");
});
