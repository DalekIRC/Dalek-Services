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
			if ($param1 !== "PLAIN"){ break; }
			$ns->sendraw(":$ns->nick SASL $origin $uid C +");
			break;
		
		case "C":
		
			$sasl[$uid]["pass"] = $param1;
			
			$tok = explode(chr(0),base64_decode($sasl[$uid]["pass"]));
		
			if (count($tok) == 2){
				$account = $tok[0];
				$pass = $tok[1];
			}
			elseif (count($tok) == 3){
				$account = $tok[1];
				$pass = $tok[2];
			}
			
			if (df_verify_userpass($account,$pass)){
				$ns->svslogin($uid,$account);
				$ns->sendraw(":$ns->nick SASL $origin $uid L $account");
				$ns->sendraw(":$ns->nick SASL $origin $uid D S");
			}
			else {
				$ns->sendraw(":$ns->nick SASL $origin $uid D F");
			}
			break;
	}
});
			
			
			