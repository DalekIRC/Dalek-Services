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


class SASL {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "SASL";
	public $description = "SASL (IRCv3)";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;


	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or early hooks */
	function __construct()
	{
		hook::func(HOOKTYPE_PRE_CONNECT, 'SASL::create_table');
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		hook::del(HOOKTYPE_WELCOME, 'SASL::hook_uid');
		hook::del(HOOKTYPE_PRE_CONNECT, 'SASL::create_table');
		hook::del(HOOKTYPE_START, 'SASL::on_connect');
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init() : bool
	{
		if (!CommandAdd($this->name, 'SASL', 'SASL::cmd_sasl', 0))
			return false;

		return true;

		hook::func(HOOKTYPE_WELCOME, 'SASL::hook_uid');
		hook::func(HOOKTYPE_START, 'SASL::on_connect');
	}

	static function create_table($u)
	{
		$conn = sqlnew();

		$table = sqlprefix()."fingerprints_external";	
		
		$conn->query("CREATE TABLE IF NOT EXISTS $table (
			id int NOT NULL AUTO_INCREMENT,
			account varchar(255),
			ip varchar(255),
			fingerprint varchar(255),
			PRIMARY KEY (id)
		)");
		$conn->close();
	}

	function on_connect($u)
	{
		$conn = sqlnew();
		$query = "SELECT * FROM ".sqlprefix()."user";
		$prep = $conn->prepare($query);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result || !$result->num_rows)
			return;
		
		while($row = $result->fetch_assoc())
		{
			if (IsRegUser($row['nick']) && !$row['account'])
			{
				send_numeric($row['nick'],231,	"⚠️ ALERT ⚠️","That username is registered. If it's your account, please authenticate for it using SASL.",
												"If you do not authenticate, your nick will be changed.");
			}
			elseif ($row['account'] && !strcasecmp($u['account'],$u['nick']))
			{
				/* find if the account actually exists damnit */
				if (!IsRegUser($u['account']))
				{
					send_numeric($row['nick'],231,"You are logged into an account that does not exist. You will be logged out and this incident reported.");

					svslogin($row['UID'],0);
					SVSLog(LOG_WARN."User with nick '".$row['nick']."' was logged into account '".$row['account']."', which doesn't exist. They have been logged out.");
					return;
				}
				$array = array('uid' => $row['UID'], 'nick' => $row['nick'], 'account' => $row['account']);
				hook::run("auth", $array);
			}
		}
	}
	public static function hook_uid($u)
	{
		$ns = Client::find("NickServ");
		if (!$ns)
			return;
		
		$nick = new User($u['uid']);
		if (!$nick->IsUser)
			return;
		if (!isset($u['account']) || !$u['account'] || $u['account'] == "0" || $u['account'] == "*"){
			if (!IsRegUser($nick->nick)){
				return;
			}
			$ns->notice($nick->uid,"This account is registered. If this is your account,","please SASL for it using:",bold("/AUTHENICATE plain"),"Or, if you have a client certificate saved with us:",bold("/AUTHENTICATE external"));
			
		}
		elseif ($nick->nick == $u['account']) {
			$array = ['uid' => $nick->uid,'account' => $u['account'], 'nick' => $u['nick']];
			hook::run("auth", $array);
		}
	}


	public static function cmd_sasl($u)
	{
		$parv = explode(" ",$u['params']);
		
		$origin = $u['nick']->nick;
		
		$destination = $parv[0];

		if (!IsMe($destination) && strcmp($destination,"*")) /* Not for us, ignore */
			return;

		$uid = $parv[1];
		
		$cmd = $parv[2];
		
		$param1 = $parv[3] ?? NULL;
		
		$param2 = $parv[4] ?? NULL;

		new IRC_SASL($origin,$uid,$cmd,$param1,$param2);
	}
}

if (!function_exists('SendSasl'))
{
	function SendSasl($string)
	{
		S2S("SASL $string");
	}
}
class IRC_SASL {
	static $list = [];
	static $ignore = [];
	public $account;
	public $uid;
	public $source;
	public $banned;
	public $reason;
	public $check;

	function __construct($source,$uid,$cmd,$param1 = "", $param2 = "")
	{
		$this->account = NULL;
		$this->uid = $uid;
		$this->source = $source;
		$this->banned = "";
		$this->reason = "";
		if (!isset(self::$list[$uid]) && $cmd == "H")
		{
			self::$list[$uid]["host"] = $param1;
			self::$list[$uid]["ip"] = $param2;
		}
		//if in our ignore list
		
		
		if (in_array($param2,self::$ignore))
		{
			return;
		}
		elseif (isset(self::$list[$uid]) &&  $cmd == "S")
		{
			self::$list[$uid]["mech"] = strtoupper($param1);
			self::$list[$uid]["key"] = $param2 ?? NULL;
			if (!strcasecmp($param1,"*"))
			{
				$this->reason = "User aborted";
				$this->fail();
				return;
			}
			if (strcasecmp($param1,"plain") && strcasecmp($param1,"external"))
			{
				$this->account = "Unsupported mechanism: $param1";
				$this->fail();
				return;
			}
			$this->check = $this->check_pass(self::$list[$uid]["key"]);
			if ($param1 !== "EXTERNAL" && $this->check == 0)
				SendSasl("$source $uid C +");
			
			elseif ($param1 == "EXTERNAL" && $this->check == 0)
			{
				$this->fail();
				return;
			}
		
			elseif ($this->check > 0)
				$this->success($this->check,$source);
		}
		elseif (isset(self::$list[$uid]) && $cmd == "C")
		{
			if ($param1 == "+")
			{
				if (self::$list[$uid]["mech"] == "EXTERNAL")
					if ($this->check_pass(self::$list[$uid]["key"]))
						$this->fail();
				else
				{	
					$this->reason = "Client asked to do PLAIN but is trying to continue as if it has sent us a CertFP and is waiting for us to process it";
					$this->fail();
				}
			}
			else {
				self::$list[$uid]["pass"] = $param1;
				$this->check = $this->check_pass($param1);
				if ($this->check == 0)
					$this->fail();
				elseif ($this->check > 0)
					$this->success($this->check,$source);
			}
		}
		elseif (isset(self::$list[$uid]) && $cmd == "D")
			if ($param1 == "A")
				unset(self::$list[$uid]);			
	}
	private function success(int $i, $source = NULL)
	{

		if ($i)
		{
			SVSLog("[".self::$list[$this->uid]['host']."|".self::$list[$this->uid]['ip']."] $this->uid identified using SASL for account: $this->account $this->reason");
			svslogin($this->uid,$this->account);
		}

		$conn = sqlnew();
		$prep = $conn->prepare("UPDATE ".sqlprefix()."user SET account = ? WHERE UID = ?");
		$prep->bind_param("ss",$this->account,$this->uid);
		$prep->execute();
		$conn->close();
		SendSasl("$source $this->uid D S");
		fail2ban(self::$list[$this->uid]['ip'], 0);

		/* if they're already connected, run the auth hook */
		$client = new User($this->uid);
		if ($client->IsUser)
		{
			$array = ['uid' => $client->uid, 'account' => $this->account, 'nick' => $client->nick];
			hook::run(HOOKTYPE_AUTHENTICATE, $array);
		}
		unset(self::$list[$this->uid]);
		
	}
	private function fail()
	{
		$ns = Client::find("NickServ");
		$r = ($this->reason) ? " ".$this->reason : "";
		if (!isset($this->account) || !strlen($this->account))
			$this->account = "No account provided";
		SVSLog("[".self::$list[$this->uid]['host']."|".self::$list[$this->uid]['ip']."] $this->uid failed to identify ($this->account)$r");
		
		SendSasl("$this->source $this->uid D F");
		fail2ban(self::$list[$this->uid]['ip'],1);
		unset(self::$list[$this->uid]);
	}
		
	 function check_pass($passwd)
	{
		if (!$passwd) {
			return false;
		}
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

		$table = sqlprefix()."fingerprints_external";	
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT * FROM $table WHERE fingerprint = ? LIMIT 1");
		$prep->bind_param("s", self::$list[$this->uid]["key"]);
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
		
