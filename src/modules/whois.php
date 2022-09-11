<?php
/*				
//	(C) 2022 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title:		WHOIS
//				
\\	Desc:		WHOIS command
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

/* class name needs to be the same name as the file */
class whois {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "whois";
	public $description = "Provides WHOIS compatibility";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
	
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		/* Params: CommandAdd( this module name, command keyword, function, parameter count)
		 * the function is a string reference to this class, the cmd_elmer method (function)
		 * The last param is expected parameter count for the command
		 * (both point to the same function which determines)
		*/

		if (!CommandAdd($this->name, 'WHOIS', 'whois::cmd_whois', 1))
			return false;

		/* we use this information in whois */
		hook::func("raw", 'whois::protoctl');
		hook::func("raw", 'whois::server');

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_whois($u)
	{
		global $servertime;
	
		$nick = $u['nick'];
	
		$parv = explode(" ",$u['params']);
		$user = $parv[1];
		if (!isset($user) || !$user || !strlen($user))
			return;

		/* $nick = the requester User object
		 * $whois = target user object
		 */
		$whois = new User($user);

		if (!$whois->IsUser) /* User not found, return*/
		{
			S2S("401 $nick->nick $user :No such nick/channel");
			S2S("318 $nick->nick $user :End of /WHOIS list.");
			return;
		}

		/* figure out if we are showing a vhost, cloak, whatever */
		$hostmask = (strpos($whois->usermode,"x")) ? $whois->cloak : $whois->realhost;
		$showhost = (strpos($whois->usermode,"t")) ? $whois->cloak : $hostmask;

		/* Line 1 of whois */
		S2S("311 $nick->nick $whois->nick $whois->ident $showhost * :$whois->gecos");
		
		/* if they are oper, or whoising themselves, show them usermode and connection information */
		if (strpos($nick->usermode,"o") !== false || $nick->uid == $whois->uid)
		{
			/* send it */
			S2S("379 $nick->nick $whois->nick :is using modes $whois->usermode");
			S2S("378 $nick->nick $whois->nick :is connecting from *@$whois->realhost $whois->ip");
		}

		/* if they are using a registered nick */
		if (strpos($whois->usermode,"r"))
		{
			/* let em know */
			S2S("307 $nick->nick $whois->nick :is identified for this nick (+r)");
		}

		/* find their channels */
		$chanlist = $whois->channels;
		$full_list = NULL;

		/* cycle through each channel, its modes, and channel membership */
		for ($p = 0; isset($chanlist['list'][$p]); $p++)
		{
			
			$secret = NULL;
			$chanmode = NULL;
			$chan = new Channel($chanlist['list'][$p]);
			
			if ($chan->modes)
			{
				/* channelmodes +s and +p to mark them as hidden */
				if (strpos($chan->modes,"s")  !== false || strpos($chan->modes,"p") !== false)
				{
					$secret = true;
				}
				else
				{
					$secret = false;
				}
			}

			/* figure out user/channel membership */
			if ($chanlist['mode'])
			{
				$char = $chanlist['mode'][$p];
				
				if (strpos($char,"q") !== false)
				{
					$chanmode .= "~";
				}
				if (strpos($char,"a") !== false)
				{
					$chanmode .= "&";
				}
				if (strpos($char,"o") !== false)
				{
					$chanmode .= "@";
				}
				if (strpos($char,"h") !== false)
				{
					$chanmode .= "%";
				}
				if (strpos($char,"v") !== false)
				{
					$chanmode .= "+";
				}
			
			}
			$sec = ($secret) ? "!" : "";

			/* show secret channels to opers and to self */
			if ($secret && (strpos($nick->usermode,"o") || $whois->uid == $nick->uid))
			{
				$full_list .= $chanmode.$sec.$chanlist['list'][$p]." ";
			}
			if (!$secret)
			{
				$full_list .= $chanmode.$chanlist['list'][$p]." ";
			}
		}

		/* tidy the buffer up and send*/
		$chan_list = self::chan_buffer($full_list);
		if (!empty($chan_list))
			foreach($chan_list as $chans)
				S2S("319 $nick->nick $whois->nick :$chans");
		
		/* find which server they're using */
		$sv = find_serv($whois->sid);
		S2S("312 $nick->nick $whois->nick ".$sv['servername']." :".$sv['version']);
		
		/* show IRCop status */
		if (strpos($whois->usermode,"o"))
			S2S("313 $nick->nick $whois->nick :is an IRC Operator (+o)");

		/* show if they are using a secure connection */
		if (strpos($whois->usermode,"z"))
			S2S("671 $nick->nick $whois->nick :is using a Secure Connection (+z) [".$whois->meta->tls_cipher."]");

		/* show their account */
		if ($whois->account)
			S2S("330 $nick->nick $whois->nick $whois->account :is logged in as");

		/* show their swhois lines */
		if ($swhois = self::get_swhois($whois->uid))
		{
			foreach ($swhois['swhois'] as $whoistok)
			{
				S2S("320 $nick->nick $whois->nick :$whoistok ");
			}
		}

		/* whois finished */
		S2S("318 $nick->nick $whois->nick :End of /WHOIS list.");

		/* if the nick has +W let them know about the whois on them */
		if (strpos($whois->usermode,"W"))
			S2S("NOTICE $whois->nick :$nick->nick did a /WHOIS on you.");

		
	}

	/* for server linking */
	public static function protoctl($u)
	{
		global $_LINK;
	
		$parv = explode(" ",$u['string']);
		if ($parv[0] !== "PROTOCTL")
		{ 
			return;
		}
		for ($i = 1; isset($parv[$i]); $i++)
		{
			$tok = explode("=",$parv[$i]);
			if ($tok[0] == "SID")
			{
				$_LINK = $tok[1];
			}
		}
	}

	/* for server linking */
	public static function server($u)
	{
		global $_LINK, $sql;
	
		$parv = explode(" ",$u['string']);
		if ($parv[0] !== "SERVER")
		{ 
			return;
		}
		$sid = $_LINK;
		$_LINK = NULL;
		$name = $parv[1];
		$hops = $parv[2];
		$desc = str_replace("$parv[0] $parv[1] $parv[2] $parv[3] ","",$u['string']);
		
		$sql->sid(array('server' => $name,'hops' => $hops,'sid' => $sid,'desc' => $desc));
	}

	/* return a nice buffer of channels */
	public static function chan_buffer($chans)
	{
		if (!strlen($chans))
			return NULL;
	
		$buffer = array();
		if (strlen($chans) <= 230)
		{
			$buffer[] = $chans;
			return $buffer;
		}
	
		$a = "";
	
		$chan = explode(" ",$chans);
		for ($i = 0; isset($chan[$i]); $i++)
		{
			if (strlen($a." ".$chan[$i]) <= 230)
			{
				$a .= " ".$chan[$i];
			}
			else
			{
				$buffer[] = trim($a);
				$a = "";
				$i--;
			}
		}
		if (strlen($a))
			$buffer[] = $a;
	
		return $buffer;
	}

	/* get the users swhois lines */
	public static function get_swhois($uid)
	{
		$user = new User($uid);
		if (!$user->IsUser)
		{
			return;
		}
		else
		{
			$conn = sqlnew();
			$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."swhois WHERE uid = ? ORDER BY priority ASC");
			$prep->bind_param("s", $uid);
			$prep->execute();
			$result = $prep->get_result();
			
			if ($result->num_rows == 0){ return false; }
			else
			{
				$swhois = array();
				$tag = array();
				
				while($row = $result->fetch_assoc())
				{
					$swhois[] = $row['swhois'];
					$tag[] = $row['tag'];
				}
			}
		}
		$return = array('swhois' => $swhois, 'tag' => $tag);
		$prep->close();;
		return $return;
	}
}
