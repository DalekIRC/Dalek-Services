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


hook::func("start", function($u)
{
	
	global $sql,$ns,$cf,$wpconfig,$nickserv;
	if ($nickserv['login_method'] !== "wordpress"){
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
		if (wp_IsRegUser($row['nick']) && !$row['account'])
		{
			$ns->notice($row['UID'],"This account is registered. If this is your account,");
			$ns->notice($row['UID'],"please identify for it using:");
			$ns->notice($row['UID'],"/msg $ns->nick identify password");
		}
		elseif ($row['account'] && strtolower($row['account']) == strtolower($row['nick']) )
		{ 
			$ns->svs2mode($row['UID'],"+r");
			nickserv::run("identify", array('nick' => $row, 'account' => $row['account']));
		}
	}
	
});
hook::func("UID", function($u)
{
	global $ns,$nickserv;
	if ($nickserv['login_method'] !== "wordpress"){
		return;
	}
	if (!$ns)
		return;
	
	$nick = new User($u['uid']);
	if (!isset($u['account'])){
		if (!wp_IsRegUser($nick->nick)){
			return;
		}
		$ns->notice($nick->uid,"This account is registered. If this is your account,");
		$ns->notice($nick->uid,"please identify for it using:");
		$ns->notice($nick->uid,"/msg $ns->nick identify password");
	}
	else {
		$ns->svs2mode($nick->nick,"+r");
	}
});

hook::func("raw", function($u)
{
	global $nickserv;
	if ($nickserv['login_method'] !== "wordpress"){
		return;
	}
	$tok = explode(" ",$u['string']);
	if ($tok[1] !== "SASL")
	{
		return;
	}
	nickserv::run("sasl", array('sasl' => $u['string']));
}); 	
nickserv::func("sasl", function($u){
	
	global $ns,$nickserv,$sasl;
	if ($nickserv['login_method'] !== "wordpress")
		return;

	$parv = explode(" ",$u['sasl']);
	
	$origin = mb_substr($parv[0],1);
	
	$uid = $parv[3];
	
	$cmd = $parv[4];
	
	$param1 = $parv[5] ?? NULL;
	
	$param2 = $parv[6] ?? NULL;

	$sasl = new IRC_SASL($origin,$uid,$cmd,$param1,$param2);
});

function SendSasl($string)
{
	S2S("SASL $string");
}
class IRC_SASL {
	function __construct($source,$uid,$cmd,$param1 = "", $param2 = "")
	{
		global $_SASL;
		
		$this->uid = $uid;
		$this->source = $source;
		if (!isset($_SASL[$uid]) && $cmd == "H")
		{
			$_SASL[$uid]["host"] = $param1;
			$_SASL[$uid]["ip"] = $param2;
		}
		elseif (isset($_SASL[$uid]) && ($cmd == "S" || $cmd == "C") && $param1 == "PLAIN")
		{
			$_SASL[$uid]["mech"] = $param1;
			$_SASL[$uid]["key"] = $param2;
			$this->check = $this->check_pass($_SASL[$uid]["key"]);
			if ($this->check == 0)
				SendSasl("$source $uid C +");

			elseif ($this->check > 0)
				$this->success($this->check);
		}
		elseif (isset($_SASL[$uid]) && $cmd == "C")
		{
			if ($param1 == "+")
			{
				SendSasl("$source $uid C +");
				return;
			}
			else {
				$_SASL[$uid]["pass"] = $param1;
				$this->check = $this->check_pass($param1);
				var_dump($this->check);
				if ($this->check == 0)
					$this->fail();
				elseif ($this->check > 0)
					$this->success($this->check);
			}
		}
		elseif (isset($_SASL[$uid]) && $cmd == "D")
			if ($param1 == "A")
				unset($_SASL[$uid]);
			
	}
	private function success(int $i)
	{
		global $ns,$_SASL;

		if ($i == 2)
		{
			$ns->log("[".$_SASL[$this->uid]['host']."|".$_SASL[$this->uid]['ip']."] $this->uid provided an invitation code");
			S2S(":$ns->nick SVSLOGIN * $this->uid INVITED");
		}
		elseif ($i == 1)
		{
			$ns->log("[".$_SASL[$this->uid]['host']."|".$_SASL[$this->uid]['ip']."] $this->uid identified using SASL for account: $this->account");
			S2S(":$ns->nick SVSLOGIN * $this->uid $this->account");
		}

		SendSasl("$this->source $this->uid D S");

		unset($_SASL[$this->uid]);
	}
	private function fail()
	{
		global $ns,$_SASL;		
		$ns->log("[".$_SASL[$this->uid]['host']."|".$_SASL[$this->uid]['ip']."] $this->uid failed to identify.");
		unset($_SASL[$this->uid]);
	}
		
	 function check_pass($passwd)
	{
		global $_SASL;

		$tok = explode(chr(0),base64_decode($passwd));
		if (!$tok)
			return false;

		if (count($tok) < 2)
			return false;

		if (count($tok) == 2)
		{
			$account = $tok[0];
			$pass = $tok[1];
		}
		elseif (count($tok) == 3)
		{
			$account = $tok[1];
			$pass = $tok[2];
		}
		$this->account = $account;
		if (!isset($account) || strlen($account) == 0)
			return 3;
		$wp_user = new WPUser($account);
		if ($wp_user->ConfirmPassword($pass) || $var = is_invite($account,$pass))
		{
			if (isset($var))
			{
				if ($var == true)
					return 2;
				else return 1;
			}	
			else return 1;
		}
		else
			return 0;
	}
		
}
		
