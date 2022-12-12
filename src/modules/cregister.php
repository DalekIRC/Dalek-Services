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
\\	Title:		CREGISTER
//				
\\	Desc:		CREGISTER compatibility
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
class cregister {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "cregister";
	public $description = "Provides CREGISTER compatibility";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	function __init()
	{
		if (!CommandAdd($this->name, 'CREGISTER', 'cregister::cmd_cregister', 1))
			return false;

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_cregister($u)
	{
		$nick = $u['nick'];
		$chan = new Channel($u['dest']);
		if ($chan->IsReg)
		{
			SVSLog("[BUG] User tried to register already registered channel.", "[CREGISTER] ");
			return;
		}
		self::do_register($chan->chan, $nick->account);
		$chan->SetMode("+qr " . $nick->nick);
		$mtags = generate_new_mtags();
		$mtags['draft/reply'] = $u['mtags']['msgid'];
		SVSLog("User $nick->nick ($nick->ident@$nick->realhost) registered channel $chan->chan under account $nick->account","[CREGISTER] ");
		$tags = array_to_mtag($mtags);
		S2S($tags . "NOTICE $nick->uid :Channel $chan->chan registered under your account: $nick->account");
	}
	public static function do_register($chan, $owner)
	{
		$servertime = servertime();
		$conn = sqlnew();
		if (!$conn)
			return false;
		$prep = $conn->prepare("INSERT INTO ".sqlprefix()."chaninfo (channel, owner, regdate) VALUES (?, ?, ?)");
		$prep->bind_param("sss",$chan,$owner,$servertime);
		$prep->execute();
		
		$permission = "owner";
		$prep = $conn->prepare("INSERT INTO ".sqlprefix()."chanaccess (channel, nick, access) VALUES (?, ?, ?)");
		$prep->bind_param("sss",$chan,$owner,$permission);
		$prep->execute();
		return true;
	}
}
