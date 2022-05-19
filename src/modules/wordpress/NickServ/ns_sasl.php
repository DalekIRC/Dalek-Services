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
\\	Desc: Provides default SASL (plain & external)
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

hook::func("preconnect", function($u)
{
	$conn = sqlnew();

	$table = "dalek_fingerprints_external";	
	
	$conn->query("CREATE TABLE IF NOT EXISTS $table (
		id int NOT NULL AUTO_INCREMENT,
		account varchar(255),
		ip varchar(255),
		fingerprint varchar(255),
		PRIMARY KEY (id)
	)");

});
hook::func("start", function($u)
{
	
	global $sql,$cf,$wpconfig,$nickserv;
	if ($nickserv['login_method'] !== "wordpress"){
		return;
	}
	$ns = Client::find("NickServ");
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
			hook::run("auth", array('uid' => $row['UID'], 'nick' => $row, 'account' => $row['account']));
		}
	}
	
});
hook::func("UID", function($u)
{
	global $nickserv;
	if ($nickserv['login_method'] !== "wordpress"){
		return;
	}
	$ns = Client::find("NickServ");
	if (!$ns)
		return;
	
	$nick = new User($u['uid']);
	if (!$nick->IsUser)
		return;
	if (!isset($u['account']) && $u['account'] !== 0 && $u['account'] !== "*"){
		if (!wp_IsRegUser($nick->nick)){
			return;
		}
		$ns->notice($nick->uid,"This account is registered. If this is your account,");
		$ns->notice($nick->uid,"please identify for it using:");
		$ns->notice($nick->uid,"/msg $ns->nick identify password");
	}
	elseif ($nick->nick == $u['account']) {
		$ns->svs2mode($u['nick'],"+r");
		hook::run("auth", ['uid' => $nick->uid,'account' => $u['account'], 'nick' => $u['nick']]);
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
	
	global $nickserv,$sasl;
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
		global $_SASL,$saslignore;
		
		$this->uid = $uid;
		$this->source = $source;
		$this->banned = "";
		$this->reason = "";
		if (!isset($_SASL[$uid]) && $cmd == "H")
		{
			$_SASL[$uid]["host"] = $param1;
			$_SASL[$uid]["ip"] = $param2;
		}

		//if in our ignore list
		//make sure we're not reading an empty array
		if (!isset($saslignore))
			$saslignore = array();
		if (in_array($param2,$saslignore))
		{
			/* do nothing */
		}
		elseif (isset($_SASL[$uid]) && $cmd == "S")
		{
			$_SASL[$uid]["mech"] = $param1;
			$_SASL[$uid]["key"] = $param2;

			$this->check = $this->check_pass($_SASL[$uid]["key"]);
			if ($param1 !== "EXTERNAL" && $this->check == 0)
				SendSasl("$source $uid C +");
			elseif ($param1 == "EXTERNAL" && $this->check == 0)
			{
				SendSasl("$source $UID D F");
				$this->fail();
				return;
			}
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
		global $_SASL;

		$ns = Client::find("NickServ");

		if ($i)
		{
			$ns->log("[".$_SASL[$this->uid]['host']."|".$_SASL[$this->uid]['ip']."] $this->uid identified using SASL for account: $this->account $this->reason");
			S2S(":$ns->nick SVSLOGIN * $this->uid $this->account");
		}

		SendSasl("$this->source $this->uid D S");
		fail2ban($_SASL[$this->uid]['ip'], 0);

		/* if they're already connected, run the auth hook */
		$client = new User($this->uid);
		if ($client->IsUser)
			hook::run("auth", ['uid' => $client->uid, 'account' => $this->account, 'nick' => $client->nick]);

		unset($_SASL[$this->uid]);
		
	}
	private function fail()
	{
		global $_SASL;
		$ns = Client::find("NickServ");
		$r = ($this->reason) ? " ".$this->reason : "";
		if (!isset($this->account) || !strlen($this->account))
			$this->account = "No account provided";
		$ns->log("[".$_SASL[$this->uid]['host']."|".$_SASL[$this->uid]['ip']."] $this->uid failed to identify ($this->account)$r");
		
		SendSasl("$this->source $this->uid D F");
		fail2ban($_SASL[$this->uid]['ip'],1);
		unset($_SASL[$this->uid]);
	}
		
	 function check_pass($passwd)
	{
		global $_SASL;

		if (ctype_xdigit($passwd))
		{
			if ($this->check_fingerprint($passwd))
				return 1;
		}
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
		if (!isset($account) || strlen($account) == 0){
			$this->reason = "(No account provided)";
			SendSASL("$this->source $this->uid D F");
			return 0;
		}
		$wp_user = new WPUser($account);
		if (!$wp_user->IsUser)
		{
			$this->reason = "(Account doesn't exist)";
			SendSASL("$this->source $this->uid D F");
			return 0;
		}
		if (function_exists('_is_disabled'))
			if (_is_disabled($wp_user))
			{
				SendSASL("$this->source $this->uid D F");
				$this->reason = "(User is disabled on the website)";
				return 0;
			}

		if (!$wp_user->confirmed)
		{
			S2S("SVSLOGIN * $this->uid 0");
			SendSASL("$this->source $this->uid D F");
			$this->reason = "(User has not confirmed their email)";
			return 0;
		}
		if ($wp_user->ConfirmPassword($pass) || $var = is_invite($account,$pass))
		{
			if (isset($var) && $var == true)
				$this->reason = "(Invitation code)";
			else
				$this->reason = "(PLAIN)";
			return 1;
		}
		$this->reason = "(Invalid password)";
		return 0;
	}
	function check_fingerprint($fp)
	{
		global $_SASL;
		
		$table = "dalek_fingerprints_external";	
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT account FROM $table WHERE ip = ? and fingerprint = ? LIMIT 1");
		$prep->bind_param("ss", $_SASL[$this->uid]['ip'], $_SASL[$this->uid]["key"]);
		$prep->execute();

		if (!($result = $prep->get_result()))
			return; // we return silently so the user may continue another sasl method
			
		if ($result->num_rows == 0)
			return; // we return silently so the user may continue another sasl method
		
		$row = $result->fetch_assoc();
		if (!$row['account'])
			return; // we return silently so the user may continue another sasl method
		$user = new WPUser($row['account']);
		if (_is_disabled($user) || !$user->confirmed)
			return 0;
		$this->reason = "(CertFP)";
		$this->account = $row['account'];
		return 1;
	}
		
}
		
