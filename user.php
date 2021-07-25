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
\\	Title: User
//	
\\	Desc: User class for easy callin's lol
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


class User {
	
	public function __construct($user)
	{
		
		$u = find_person($user);
		
		if (!$u)
		{
			$this->IsUser = false; return;
		}
		else { $this->IsUser = true; }
		$this->nick = $u['nick'];
		$this->uid = $u['UID'];
		$this->ts = $u['timestamp'];
		$this->ident = $u['ident'];
		$this->usermode = $u['usermodes'];
		$this->realhost = $u['realhost'];
		$this->cloak = $u['cloak'];
		$this->ip = $u['ip'];
		$this->account = (isset($u['account'])) ? $u['account'] : false;
		$this->fingerprint = (isset($u['fingerprint'])) ? $u['fingerprint'] : false;
		$this->sid = $u['SID'];
		$this->tls = (strpos($u['usermodes'],"z")) ? true : false;
	}
	function NewNick($nick)
	{
		global $serv,$servertime,$cf;
		
		if (!$this->IsUser)
		{ 
			return false;
		}
		if (!validate_nick($nick))
		{
			return false;
		}
		$serv->sendraw(":".$cf['sid']." SVSNICK ".$this->nick." $nick $servertime");
		update_nick($this->uid,$nick,$servertime);
		$this->nick = $nick;
	}
	function SetMode($mode)
	{
		$charToAdd = NULL;
		$charToDel = NULL;
		if ($mode[0] !== "+" && $mode[0] !== "-")
		{
			return false;
		}
		
		if (!isset($mode[1]))
		{
			return false;
		}
		for ($i = 0; isset($mode[$i]); $i++)
		{
			$tok = $mode[$i];
			
			if ($tok == "+")
			{
				$switch = "add";
				$i++;
			}
			
			elseif ($tok == "-")
			{
				echo "Changed switch to del\n";
				$switch = "del";
				$i++;
			}
			
			if ($switch == "add")
			{
				$charToAdd .= $mode[$i];
			}
			elseif ($switch == "del")
			{
				$charToDel .= $mode[$i];
			}
			if (!$charToDel && !$charToAdd)
			{
				return false;
			}
		}
	
		$validate = validate_modechange($this->usermode,$charToAdd,$charToDel);
		if (!$validate)
		{		
			return;
		}
		sendumode($this->uid,$validate['ToSet']);
		update_usermode($this->uid,$validate['NewModes']);
		return true;
	}
		
}


function sendumode($uid,$mode)
{
	global $serv;
	
	$serv->svs2mode($uid,$mode);
	return;
}

function validate_modechange($modesThatWeHave,$modesToAdd,$modesToDel)
{
	$AddModeString = NULL;
	$DelModeString = NULL;

	$NewModes = $modesThatWeHave;
	
	for ($i = 0; $i < strlen($modesToAdd); $i++)
	{

		if (!strpos($modesThatWeHave,$modesToAdd[$i]))
		{
			$SetTheMode .= $modesToAdd[$i];
		}
	}
	
	if (isset($SetTheMode))
	{	
		$AddModeString = "+".$SetTheMode ?? NULL;
		$NewModes = $modesThatWeHave.$SetTheMode;
	}
	
	for ($i = 0; $i < strlen($modesToDel); $i++)
	{
		
		if (strpos($NewModes,$modesToDel[$i]))
		{
			$UnsetTheMode .= $modesToDel[$i];
		}
	}
	
	if (isset($UnsetTheMode))
	{
		$DelModeString = "-".$UnsetTheMode ?? NULL;
		
		for ($i = 0; $i < strlen($UnsetTheMode); $i++)
		{
			if (strpos($NewModes,$UnsetTheMode[$i]))
			{
				$NewModes = str_replace($UnsetTheMode[$i],"",$NewModes);
			}
		}
	}
	$TheEntireStringOfModesThatWeAreGoingToSetOnTheUser = $AddModeString.$DelModeString;
	if (!$TheEntireStringOfModesThatWeAreGoingToSetOnTheUser)
	{
		return false;
	}
	$return = [
		'ToSet' => $TheEntireStringOfModesThatWeAreGoingToSetOnTheUser,
		'NewModes' => $NewModes
	];
	
	return $return;
}
		

function validate_modechar($string)
{
	if (!s($string))
	{
		return false;
	}
}

function validate_nick($string)
{
	
	for ($i = 0; $i < strlen($string); $i++){
		$val = $string[$i];
		if ($i == 0){
			
			if (!ctype_alpha($val)){
				
				if ((ord($val) >= 91 && ord($val) <= 96) || (ord($val) >= 123 && ord($val) <= 125)){ continue; }
				else { return false; }
			}
		}
		else {
			if ((ord($val) >= 65 && ord($val) <= 125) || (ord($val) >= 48 && ord($val) <= 57) || ord($val) == 45){ continue; }
				else { return false; }
		}
	}
	return true;
}