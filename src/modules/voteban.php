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
\\	Title:		VOTEBAN
//				
\\	Desc:		VOTEBAN command
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
class voteban {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "voteban";
	public $description = "Provides VOTEBAN compatibility";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		$conn = sqlnew();
		$conn->query("CREATE TABLE IF NOT EXISTS " . sqlprefix() . "votebans (
			id int AUTO_INCREMENT NOT NULL,
			uid varchar(9) NOT NULL,
			voter varchar(255) NOT NULL,
			channel varchar(255) NOT NULL,
			timestamp int NOT NULL,
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
		CommandAdd('voteban', "VOTEBAN", 'voteban::cmd_voteban', 3);
		hook::func(HOOKTYPE_CHANNELMODE_PARAM, 'voteban::mode_hook');
		return true;
	}

	public static function mode_hook($m)
	{
		if (strcasecmp($m['char'],"y")) // we only care about channelmode +y
			return;
		set_channel_setting($m['channel'], "voteban", ($m['switch'] == "+") ? $m['param'] : NULL);
	}
	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_voteban($u)
	{
		$parv = split($u['raw']);
		$parv[0] = NULL;
		$parv[1] = NULL;
		$parv = split(glue($parv));
		$chan = new Channel($parv[0]);
		$targ = new User($parv[1]);

		$error = NULL;
		if (!self::make_vote_against($chan, $targ, $u['nick'], $error) && $error)
		{
			sendnotice($u['nick'], NULL, [], $error);
			return;
		}
		
		sendnotice($u['nick'], NULL, [], "Thank you for your vote.");
		$votes = self::get_num_votes($chan, $targ);
		$req = (int)get_channel_setting($chan, "voteban");
		if ($votes >= $req)
		{
			global $sql;
			$banmask = (isset($targ->account)) ? "~account:$targ->account" : "*!$targ->ident@$targ->cloak";
			$chan->SetMode("+b $banmask");
			S2S("KICK $chan->chan $targ->nick :You have been voted out.");
			$sql->delete_ison($chan->chan, $targ->uid);
			self::delete_votes($chan, $targ);
		}
		else S2S("PRIVMSG $chan->chan :Somebody voted to ban $targ->nick [". cal_percentage($votes, $req). "%]");
		
	}
	public static function delete_votes(Channel $channel, User $user)
	{
		$conn = sqlnew();
		$c = strtolower($channel->chan);
		$prep = $conn->prepare("DELETE FROM " . sqlprefix() . "votebans WHERE uid = ? AND channel = ?");
		$prep->bind_param("ss", $user->uid, $c);
		$prep->execute();
	}
	public static function get_num_votes(Channel $channel, User $user)
	{
		$conn = sqlnew();
		$c = strtolower($channel->chan);
		$prep = $conn->prepare("SELECT * FROM " . sqlprefix() . "votebans WHERE uid = ? AND channel = ?");
		$prep->bind_param("ss", $user->uid, $c);
		$prep->execute();
		$result = $prep->get_result();
		if (!$result || !$result->num_rows)
			return 0;
		return $result->num_rows;
	}

	public static function get_num_votes_from(Channel $channel, User $user, User $from)
	{
		$conn = sqlnew();
		$c = strtolower($channel->chan);
		$voter = strtolower($from->account);
		$prep = $conn->prepare("SELECT * FROM " . sqlprefix() . "votebans WHERE uid = ? AND channel = ? AND lower(voter) = ?");
		$prep->bind_param("sss", $user->uid, $c, $voter);
		$prep->execute();
		$result = $prep->get_result();
		if (!$result || !$result->num_rows)
			return 0;
		return $result->num_rows;
	}
	public static function make_vote_against(Channel $channel, User $user, User $from, &$error)
	{
		if (self::get_num_votes_from($channel, $user, $from) > 0)
		{
			$error = "You may only vote against a user one time";
			return false;
		}
		$c = strtolower($channel->chan);
		$ts = servertime();
		$conn = sqlnew();
		$prep = $conn->prepare("INSERT INTO " . sqlprefix(). "votebans (channel, uid, voter, timestamp) VALUES (?, ?, ?, ?)");
		$prep->bind_param("ssss", $c, $user->uid, $from->account, $ts);
		$prep->execute();
		return true;

	}
}
