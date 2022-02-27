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
\\	Title: SASL
//	
\\	Desc: Provides default SASL (plain)
//	
\\	
//	
\\	
//	
\\	Version: 1
//				
\\	Author:	Valware
//				
*/


hook::func("raw", function($u)
{
	global $nickserv;
	if ($nickserv['login_method'] !== "default"){
		return;
	}
	$tok = explode(" ",$u['string']);
	if ($tok[1] !== "SASL")
	{
		return;
	}
	nickserv::run("sasl", array('sasl' => $u['string']));
}); 

hook::func("start", function($u)
{
	
	global $sql,$ns,$cf,$nickserv;
	if ($nickserv['login_method'] !== "default"){
		return;
	}
	$query = "SELECT * FROM dalek_user";
	$result = $sql::query($query);
	
	if (!$result){
		return;
	}
	
	if (mysqli_num_rows($result) == 0)
	{
		return;
	}
	
	while($row = mysqli_fetch_assoc($result))
	{
		if (df_IsRegUser($row['nick']) && !$row['account'])
		{
			$ns->notice($row['UID'],"This account is registered. If this is your account,");
			$ns->notice($row['UID'],"please identify for it using:");
			$ns->notice($row['UID'],"/msg $ns->nick identify password");
		}
		elseif ($row['account'])
		{ 
			$ns->svs2mode($row['UID'],"+r");
			nickserv::run("identify", array('nick' => $row, 'account' => $row['account']));
		}
	}
	
});
hook::func("UID", function($u)
{
	global $sql,$ns,$cf,$nickserv;
	if ($nickserv['login_method'] !== "default"){
		return;
	}
	if (!$ns){
		return;
	} // not loaded yet
	
	if (!isset($u['account'])){
		if (!df_IsRegUser($u['uid'])){
			return;
		}
		$ns->notice($u['uid'],"This account is registered. If this is your account,");
		$ns->notice($u['uid'],"please identify for it using:");
		$ns->notice($u['uid'],"/msg $ns->nick identify password");
	}
});
		
nickserv::func("sasl", function($u){
	
	global $ns,$nickserv,$sasl;
	
	if ($nickserv['login_method'] !== "default"){ return; }	// default config option
	
	$parv = explode(" ",$u['sasl']);
	
	$origin = mb_substr($parv[0],1);
	
	$uid = $parv[3];
	
	$cmd = $parv[4];
	
	$param1 = $parv[5] ?? NULL;
	
	$param2 = $parv[6] ?? NULL;
	
	switch($cmd){
		
		case "H":
		
			$sasl[$uid]["host"] = $param1;
			$sasl[$uid]["ip"] = $param2;
			break;
			
		case "S":
		
			$sasl[$uid]["mech"] = $param1;
			$sasl[$uid]["fingerprint"] = $param2;
			if ($param1 !== "PLAIN"){
				
				$ns->sendraw(":$ns->nick SASL $origin $uid D F");
				break; 
			}
			$ns->sendraw(":$ns->nick SASL $origin $uid C +");
			break;
		
		case "C":
			$sasl[$uid]["pass"] = $param1;
			
			$tok = explode(chr(0),base64_decode($sasl[$uid]["pass"]));
			if (count($tok) < 2)
				break;
			if (count($tok) == 2){
				$account = $tok[0];
				$pass = $tok[1];
			}
			elseif (count($tok) == 3){
				$account = $tok[1];
				$pass = $tok[2];
			}
			
			if (df_verify_userpass($account,$pass) || is_invite($account,$pass)){
				
				nickserv::run("saslconf", array(
					'uid' => $uid,
					'account' => $account)
				);
				$ns->log("[".$sasl[$uid]["host"]."|".$sasl[$uid]["ip"]."] $uid identified for account $account"); 
				$ns->svslogin($uid,$account);
				$ns->sendraw(":$ns->nick SASL $origin $uid L $account");
				$ns->sendraw("SASL $origin $uid D S");
				$sasl[$uid] = NULL;
				break;
			}
			else {
				$ns->log("[".$sasl[$uid]["host"]."|".$sasl[$uid]["ip"]."] $uid failed to identify for account $account"); 
				$ns->sendraw(":$ns->nick SASL $origin $uid D F");
			}
			break;
	}
});