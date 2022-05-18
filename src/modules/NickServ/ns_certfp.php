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
\\	Title: CERTFP
//	
\\	Desc: Allows you to manipulate your list of cert fingerprints.
//	
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/
class ns_certfp {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ns_certfp";
	public $description = "NickServ CERTFP Command";
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
		$help_string = "View and change your list of certificate fingerprints";
		$syntax = "CERTFP [ADD|DEL|LIST]";
		$extended_help = 	"$help_string\nYou must be using a cert fingerprint to add it.\n$syntax";

		if (!AddServCmd(
			'ns_certfp', /* Module name */
			'NickServ', /* Client name */
			'CERTFP', /* Command */
			'ns_certfp::cmd_certfp', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;
		
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_certfp($u)
	{
		$ns = $u['target'];

		$nick = $u['nick'];
		$parv = explode(" ",$u['msg']);
				
		if (!isset($parv[1]))
		{
			$ns->notice($nick->uid,"Incorrect syntax");
			return;
		}

		if (!$nick->account)
		{
			$ns->notice($nick->uid,"You need to be logged in to use that command.");
			return;
		}

		if (strtolower($parv[1]) == "add")
		{
			if (!isset($nick->meta->certfp))
			{
				$ns->notice($nick->uid,"You do not have a fingerprint to store.");
				return;
			}
			$ns->notice($nick->uid, ns_certfp::add_certfp($nick->ip,$nick->account,$nick->meta->certfp));
		}

		elseif (strtolower($parv[1]) == "del")
		{
			if (!isset($parv[2]))
			{
				$ns->notice($nick->uid,"You did not specify a fingerprint to delete.");
				return;
			}
			$ns->notice($nick->uid, ns_certfp::del_certfp($nick->ip,$nick->account,$parv[2]));
		}
		elseif (strtolower($parv[1]) == "list")
		{
			$table = 'dalek_fingerprints_external';
			$conn = sqlnew();
			$prep = $conn->prepare("SELECT * FROM $table WHERE account = ?");
			$prep->bind_param("s",$nick->account);
			$prep->execute();
			$result = $prep->get_result();
			if (!$result || $result->num_rows == 0)
			{
				$ns->notice($nick->uid,"You do not have any Certificate Fingerprints saved.");
				return;
			}
			$ns->notice($nick->uid,"Listing your saved Certificate Fingerprints:");
			while ($row = $result->fetch_assoc())
				$ns->notice($nick->uid,$row['fingerprint']);
		}
				

	}

	function is_certfp_already($ip, $account, $fp) : bool
	{
		$table = 'dalek_fingerprints_external';
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT * FROM $table WHERE ip = ? AND account = ? and fingerprint = ? LIMIT 1");
		$prep->bind_param("sss",$ip,$account,$fp);
		$prep->execute();

		$result = $prep->get_result();
		if (!$result)
			return false;

		if ($result->num_rows == 1)
			return true;
		return false;
	}

	function add_certfp($ip, $account, $fp) : string
	{

		/* already has this cert saved */
		if (ns_certfp::is_certfp_already($ip, $account, $fp))
			return "You already have that certfp saved.";

		if (!$fp || !strlen($fp))
			return "Failed to save certificate fingerprint. Please contact staff.";
		/* put to the table */
		$table = 'dalek_fingerprints_external';
		$conn = sqlnew();
		$prep = $conn->prepare("INSERT INTO $table (account, ip, fingerprint) VALUES (?, ?, ?)");
		$prep->bind_param("sss", $account, $ip, $fp);
		$prep->execute();

		/* check that we have properly stored and return the result */
		if (ns_certfp::is_certfp_already($ip, $account, $fp))
			return "You have added your certificate: $fp";

		return "Your certficate could not be added at this time";
	}

	function del_certfp($ip, $account, $fp) : string
	{

		/* already has this cert saved */
		if (!ns_certfp::is_certfp_already($ip, $account, $fp))
			return "Couldn't find saved certfp: $fp";

		/* delete from the table */
		$table = 'dalek_fingerprints_external';
		$conn = sqlnew();
		$prep = $conn->prepare("DELETE FROM $table WHERE account = ? AND ip = ? AND  fingerprint = ?");
		$prep->bind_param("sss", $account, $ip, $fp);
		$prep->execute();

		/* check that we have properly stored and return the result */
		if (!ns_certfp::is_certfp_already($ip, $account, $fp))
			return "You have deleted your certificate: $fp";

		return "Your certficate could not be deleted at this time";
	}
}
