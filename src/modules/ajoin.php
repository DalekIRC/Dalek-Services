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
\\	Title:		AJOIN
//				
\\	Desc:		AJOIN compatibility
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
class ajoin {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ajoin";
	public $description = "Provides AJOIN compatibility";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	function __construct()
	{
		$conn = sqlnew();
		$conn->query("CREATE TABLE IF NOT EXISTS ".sqlprefix()."ajoin (
			id INT AUTO_INCREMENT NOT NULL,
			account VARCHAR(255) NOT NULL,
			channel VARCHAR(255) NOT NULL,
			PRIMARY KEY (id)
		)");
	}

	function __init()
	{
		if (!CommandAdd($this->name, 'AJOIN', 'ajoin::cmd_ajoin', 1))
			return false;
		hook::func(HOOKTYPE_AUTHENTICATE, 'ajoin::hook_do_ajoin');
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_ajoin($u)
	{
		$nick = $u['nick'];
		$account = $u['nick']->account;
		$parv = split($u['params']);
		$flag = $parv[0];
		$subopt = $parv[1];

		if (!strcasecmp($flag,"add")){
			if (!($channel = new Channel($subopt))->IsChan)
			{ 
				S2S("NOTICE $nick->uid :Channel does not exist $subopt");
				return;
			}
			if (($reply = ajoin::ajoin_add($account,$channel->chan)) !== true)
			{
				S2S("NOTICE $nick->uid :$reply");
				return;
			}
			S2S("NOTICE $nick->uid :$reply");
			SVSLog($nick->nick." added ".$channel->chan." to the ajoin list for account $account");
			return;
		}
		elseif (!strcasecmp($flag,"del")){
			$channel = $subopt;
			if (($reply = ajoin::ajoin_del($account,$channel)) !== true)
			{
				S2S("NOTICE $nick->uid :$reply"); 
				return;
			}
			SVSLog($nick->nick." deleted $channel from the ajoin list for account $account");
			S2S("NOTICE $nick->uid :$reply");
			return;
		}
		elseif (!strcasecmp($flag,"list")){
			if (!($list = ajoin::ajoin_list($account)))
			{
				S2S("NOTICE $nick->uid :Your autojoin list is empty.");
				return;
			}
			S2S("NOTICE $nick->uid :Listing your autojoin list:");
			foreach($list as $chan){
				S2S("NOTICE $nick->uid $chan");
			}
			return;
		}
		
		return;
	}
	public static function hook_do_ajoin($u)
	{
		global $sql;
		$user = new User($u['uid']);
		if (!($list = ajoin::ajoin_list($u['account'] ?? NULL)))
			return;
		foreach ($list as $chan)
		{
			$sql->insert_ison($chan, $user->nick);
			S2S("SVSJOIN $user->nick $chan");
		}
	}
	
	static function IsAjoin($account,$channel)
	{
		$list = ajoin::ajoin_list($account) ?? NULL;

		if (!$list)
			return;
		foreach ($list as $chan)
			if ($chan == $channel)
				return true;
		return false;
	}
	static function ajoin_list($account)
	{
		$account = strtolower($account);
		$conn = sqlnew();
		if (!$conn)
			return false;
		else {
			$prep = $conn->prepare("SELECT channel FROM ".sqlprefix()."ajoin WHERE lower(account) = ?");
			$prep->bind_param("s",$account);
			$prep->execute();
			$sResult = $prep->get_result();
			if (!$sResult || $sResult->num_rows == 0)
				return false;

			$chans = [];
			while($row = $sResult->fetch_assoc())
				$chans[] = $row['channel'];

			$prep->close();
			return $chans;
		}
	}
	static function ajoin_add($account,$channel)
	{
		$account = strtolower($account);
		$conn = sqlnew();
		if (!$conn)
			return false;
		
		if (ajoin::IsAjoin($account,$channel))
			return "That channel is already on your list.";
		
		else
		{
			$prep = $conn->prepare("INSERT INTO ".sqlprefix()."ajoin (account, channel) VALUES (?, ?)");
			$prep->bind_param("ss",$account,$channel);
			$prep->execute();
			return "$channel has been added to your autojoin list";
		}
	}
	static function ajoin_del($account,$channel)
	{
		$account = strtolower($account);
		$conn = sqlnew();
		if (!$conn)
			return false;
		
		if (!ajoin::IsAjoin($account,$channel))
			return "That channel is not on your list.";
		
		else
		{
			$prep = $conn->prepare("DELETE FROM ".sqlprefix()."ajoin WHERE LOWER(account) = ? AND channel = ?");
			$prep->bind_param("ss",$account,$channel);
			$prep->execute();
			return "$channel has been deleted from your autojoin list";
		}
	}
}
