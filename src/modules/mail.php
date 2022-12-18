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
\\	Title:		MAIL
//				
\\	Desc:		MAIL command
\\				
//				This module requires that third/dalek be loaded on the unreal uplink
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

/* class name needs to be the same name as the file */
class mail {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "mail";
	public $description = "Provides MAIL compatibility";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		$conn = sqlnew();
		$conn->query("CREATE TABLE IF NOT EXISTS " . sqlprefix() . "mail (
			id int AUTO_INCREMENT NOT NULL,
			from_account varchar(255) NOT NULL,
			from_cloak varchar(255) NOT NULL,
			timestamp int NOT NULL,
			message varchar(255) NOT NULL,
			account varchar(255) NOT NULL,
			PRIMARY KEY(id)
		)");
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

		if (!CommandAdd($this->name, 'MAIL', 'mail::cmd_mail', 0))
			return false;

		hook::func(HOOKTYPE_WELCOME, 'mail::showlist');

		return true;
	}


	/** MAIL
	 * This needs the DalekIRC module for UnrealIRCd in order to work.
	 * Lets users send offline messages with /MAIL <account> <message>
	 */
	public static function cmd_mail($u)
	{
		$nick = $u['nick'];
		$tok = split($u['raw']);
		$tok[0] = NULL;
		$tok[1] = NULL;
		$dest = $tok[2];
		$tok[2] = NULL;

		$mtags = generate_new_mtags();
		$mtags["+draft/reply"] = (isset($u['mtags']['msgid'])) ? $u['mtags']['msgid'] : NULL;
		/* If the user wants to list their mail */
		if (!strcasecmp($dest,"-list"))
		{
			/* No mail found */
			if (!self::check_for_new($nick->account))
				sendnotice($nick, NULL, $mtags, "No new mail");
			else
				self::showlist(['account' => $nick->account, 'nick' => $nick->nick]);
			return;
		}
		$msg = mb_substr(glue($tok),1);

		/* Couldn't find their WordPress account! */
		if (!($target = new WPUser($dest))->IsUser)
		{
			sreply::send_fail($nick, "MAIL", "ACCOUNT_DOES_NOT_EXIST", "", "That account does not exist.");
			return;
		}

		/* User has already sent 10 messages to this person. Don't allow it */
		if (self::num_of_current($target->user_nicename, $nick->account) >= 10)
		{
			sreply::send_fail($nick, "MAIL", "MAIL_LIMIT_REACHED", "10", "You have sent the maximum number of mail messages you can send to that user.");
			return;
		}
		/* send the mail */
		self::sendto($nick, $target, $msg);
		sendnumeric("%i %c :%s", RPL_MAIL_MSGSENT, $nick, "Your message has been sent.");

		// if someone is logged in with that account, let them know they've got mail =]
		foreach (user_list_by_account($dest) as $user)
			if ($user->account != NULL && !strcasecmp($user->account,$dest))
				sendnumeric("%i %c :%s", RPL_MAIL_YOUVEGOTMAIL, $user, "You've got mail! Type ".bold("/MAIL -list")." to view");
	}
	public static function num_of_current($to)
	{
		$conn = sqlnew();
		$to = strtolower($to);
		$prep = $conn->prepare("SELECT * FROM " . sqlprefix() . "mail WHERE LOWER(account) = ? ORDER BY timestamp ASC");
		$prep->bind_param("s", $to);
		$prep->execute();
		$result = $prep->get_result();
		if (!$result || !$result->num_rows)
			return 0;
		return $result->num_rows;

		
	}
	public static function sendto(User $from, WPUser $to, String $message)
	{
		$ts = servertime();
		$conn = sqlnew();
		$cloak = "$from->nick!$from->ident@$from->cloak";
		$message = base64_encode($message);
		$prep = $conn->prepare("INSERT INTO " . sqlprefix() . "mail (from_account, from_cloak, timestamp, message, account) VALUES (?,?,?,?,?)");
		$prep->bind_param("ssiss", $from->account, $cloak, $ts, $message, $to->user_nicename);
		$prep->execute();
	}

	public static function check_for_new($account)
	{
		$account = strtolower($account);
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT * FROM " . sqlprefix() . "mail WHERE lower(account) = ? ORDER BY timestamp ASC");
		$prep->bind_param("s", $account);
		$prep->execute();

		$result = $prep->get_result();
		if (!$result || !$result->num_rows)
			return false;
		else
			return $result;
	}

	public static function showlist($u)
	{
		if (!isset($u['account']) || !($mail = self::check_for_new($u['account'])))
			return;
		$account = "";
		S2S("SPRIVMSG IRC " . $u['nick'] . " :Playing back messages you received while offline.");
		$latest_ts = 0;
		while ($row = $mail->fetch_assoc())
		{
			if ($row['timestamp'] > $latest_ts)
				$latest_ts = $row['timestamp'];
			$account = strtolower($row['account']);
			$mtags = generate_new_mtags();
			$mtags["time"] = irc_timestamp($row['timestamp']);
			$mtags["account"] = $row['from_account'];
			$mtag = array_to_mtag($mtags);
			S2S($mtag . "SPRIVMSG " . $row['from_cloak'] . " " . $u['nick'] . " :" . str_replace("\\", "\\\\", base64_decode($row['message'])) . " [to: " . $u['account'] . "]");
		}
		$conn = sqlnew();
		$prep = $conn->prepare("DELETE FROM " . sqlprefix() . "mail WHERE LOWER(account) = ? AND timestamp <= ?");
		$prep->bind_param("si", $account, $latest_ts);
		$prep->execute();
	}
}
