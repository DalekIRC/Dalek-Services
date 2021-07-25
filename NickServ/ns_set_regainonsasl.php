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
\\	Title: RegainOnSasl
//	
\\	Desc: If set to on, will recover your nick for you if it's online when you sasl with the account
//	
\\	
//	
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/

nickserv::func("setcmd", function($u)
{
	
	global $ns,$cf;
	
	$parv = explode(" ",$u['cmd']);
	if ($parv[0] !== "set"){ return; }
	if ($parv[1] !== "regainonsasl"){ return; }
	$nick = new User($u['nick']);
	if (!($account = $nick->account))
	{
		$ns->notice($nick->uid,"You must be logged in to use this command.");
		return;
	}
	if ($cf['login_method'] !== "default"){ return; }
	if (!isset($parv[2])){ return; }
	if ($parv[2] !== "on" && $parv[2] !== "off"){ return; }
	
	if ($parv[2] == "on")
	{ 
		if (!RegainOnSasl($account,"on"))
		{
			$ns->notice($nick->uid,"REGAINONSASL is already set to 'on'");
			return;
		}
		else
		{
			$ns->notice($nick->uid,"REGAINONSASL is now set to 'on'");
			return;
		}	
	}
	if ($parv[2] == "off")
	{ 
		if (!RegainOnSasl($account,"off")){
			$ns->notice($nick->uid,"REGAINONSASL is already set to 'off'");
			return;
		}
		else
		{
			$ns->notice($nick->uid,"REGAINONSASL is now set to 'off'");
			return;
		}	
	}
});


function RegainOnSasl($account,$option)
{
	
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	$opt = "regainonsasl";
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return false; }
	else
	{
		$prep = $conn->prepare("SELECT setting_value FROM dalek_account_settings WHERE account = ? AND setting_key = 'regainonsasl'");
		$prep->bind_param("s",$account);
		$prep->execute();
		$result = $prep->get_result();
		
		if ($result->num_rows == 0)
		{
		
			$prep = $conn->prepare("INSERT INTO dalek_account_settings (account, setting_key, setting_value) VALUES (?, ?, ?)");
			$prep->bind_param("sss", $account, $opt, $option);
			$prep->execute();
			$return = true;
		}
		
		else
		{
			
			while($row = $result->fetch_assoc())
			{
				$switch = $row['setting_value'];
				
				if (($switch == "on" && $option == "on") || ($switch == "off" && $option == "off")){ $return = false; }
				else
				{
					$prep = $conn->prepare("UPDATE dalek_account_settings SET setting_value = ? WHERE account = ? AND setting_key = ?");
					$prep->bind_param("sss", $option, $account, $opt);
					$prep->execute();
					$return = true;
				}
			}
		}
	}
	$prep->close();
	return $return;
}
function IsRegainOnSasl($account)
{
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return false; }
	else
	{
		$prep = $conn->prepare("SELECT setting_value FROM dalek_account_settings WHERE account = ? AND setting_key = 'regainonsasl'");
		$prep->bind_param("s", $account);
		$prep->execute();
		$result = $prep->get_result();
		
		if ($result->num_rows == 0){ return false; }
		else
		{
			
			$row = $result->fetch_assoc();
			if ($row['setting_value'] == "on"){ $return = true; }
			if ($row['setting_value'] == "off"){ $return = false; }
		}
	}
	$prep->close();;
	return $return;
}
		
		
	
nickserv::func("saslconf", function($u)
{
	
	global $ns,$recovery,$serv,$cf,$servertime;
	
	if (!IsRegainOnSasl($u['account'])){ return; }
	else {
		if ($person = new User($u['account']))
		{ 
		
			if ($person->uid !== $u['uid']){
				$ns->sendraw(":$ns->nick KILL $person->nick :Automatic recovery in progress");
			}
		}
		
		$recovery[$u['uid']] = $u['account'];
		if ($recov = new User($u['uid']))
		{
			$ns->sendraw(":".$cf['sid']." SVSNICK $recov->uid ".$recovery[$u['uid']]." $servertime"); $recovery[$u['uid']] = NULL;
		}
	}
});

hook::func("UID", function($u)
{
	
	global $ns,$recovery,$cf,$servertime;
	if (isset($recovery[$u['uid']]))
	{ 
		$ns->sendraw(":".$cf['sid']." SVSNICK ".$u['nick']." ".$recovery[$u['uid']]." $servertime"); $recovery[$u['uid']] = NULL;
	}
	return;
});

nickserv::func("setlist", function($u)
{
	
	global $ns;
	
	if (isset($u['key'])){ return; }
	if (isset($parv[0])){ return; }
	$ns->notice($u['nick'],"REGAINONSASL        Automatically regain your nick when you identify through SASL");
});
