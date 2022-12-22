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
\\	Title:		CERTFP
//				
\\	Desc:		CERTFP compatibility
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
class certfp {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "certfp";
	public $description = "Provides CERTFP compatibility";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	function __init()
	{
		if (!CommandAdd($this->name, 'CERTFP', 'certfp::cmd_certfp', 1))
			return false;

		return true;
	}

	/** This function assumes checks have been made
	 * on unreal to ensure they do have a certp and
	 * are logged in in order to use this command,
	 * so we can skip some checks.
	 */
	public static function cmd_certfp($u)
	{
		$nick = $u['nick'];
		$parv = [];
		$parv[0] = $u['dest'];
		$parv[1] = $u['params'];
		$p = split($parv[1]);
		$parv[1] = mb_substr($p[1],1);
		var_dump($nick);

		if ($parv[0] == "LIST")
		{
			$table = sqlprefix().'fingerprints_external';
			$conn = sqlnew();
			$prep = $conn->prepare("SELECT * FROM $table WHERE account = ?");
			$prep->bind_param("s",$nick->account);
			$prep->execute();
			$result = $prep->get_result();
			if (!$result || $result->num_rows == 0)
				return sreply::send_fail($nick, "CERTFP", "LIST_EMPTY", $parv[0], "You do not have any Certificate Fingerprints saved.");
			
			sreply::send_note($nick, "CERTFP", "SHOWING_LIST", $parv[0], "Listing your saved Certificate Fingerprints:");
			while ($row = $result->fetch_assoc())
				sreply::send_note($nick, "CERTFP", "IS_CERT", $parv[0], $row['fingerprint']);
		}
		elseif ($parv[0] == "ADD")
		{
			if (self::is_certfp_already($nick->account, $nick->meta->certfp))
				return sreply::send_fail($nick, "CERTFP", "ALREADY_EXISTS", $parv[0], "That Certficate Fingerprint has already been saved to your account.");

			if (!self::add_certfp("0", $nick->account, $nick->meta->certfp))
				return sreply::send_fail($nick, "CERTFP", "INTERNAL_ERROR", $parv[0], "There was an internal error. Please try again later.");
			return sreply::send_note($nick, "CERTFP", "SUCCESS", $parv[0], "Your Certificate Fingerprint of \"".$nick->meta->certfp."\" has been added.");
		}
		elseif ($parv[0] == "DEL")
		{
			if (!self::is_certfp_already($nick->account, $nick->meta->certfp))
				return sreply::send_fail($nick, "CERTFP", "NOT_FOUND", $parv[0], "That certificate fingerprint was not found: ".$parv[1]);

			if (!self::del_certfp($nick->account, $nick->meta->certfp))
				return sreply::send_fail($nick, "CERTFP", "INTERNAL_ERROR", $parv[0], "There was an internal error. Please try again later.");
			return sreply::send_note($nick, "CERTFP", "SUCCESS", $parv[0], "Your Certificate Fingerprint of \"".$parv[1]."\" has been removed.");
		}
	}
	public static function is_certfp_already($account, $fp) : bool
	{
		$table = sqlprefix().'fingerprints_external';
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT * FROM $table WHERE account = ? and fingerprint = ? LIMIT 1");
		$prep->bind_param("ss",$account,$fp);
		$prep->execute();

		$result = $prep->get_result();
		if (!$result)
			return false;

		if ($result->num_rows == 1)
			return true;
		return false;
	}

	public static function add_certfp($ip, $account, $fp) : string
	{

		/* already has this cert saved */
		if (certfp::is_certfp_already($account, $fp))
			return "You already have that certfp saved.";

		if (!$fp || !strlen($fp))
			return "Failed to save certificate fingerprint. Please contact staff.";
		/* put to the table */
		$table = sqlprefix().'fingerprints_external';
		$conn = sqlnew();
		$prep = $conn->prepare("INSERT INTO $table (ip, account, fingerprint) VALUES (?, ?, ?)");
		$prep->bind_param("sss", $ip, $account, $fp);
		$prep->execute();

		/* check that we have properly stored and return the result */
		if (certfp::is_certfp_already($account, $fp))
			return 1;

		return 0;
	}

	public static function del_certfp($account, $fp) : string
	{

		/* already has this cert saved */
		if (!certfp::is_certfp_already($account, $fp))
			return "Couldn't find saved certfp: $fp";

		/* delete from the table */
		$table = sqlprefix().'fingerprints_external';
		$conn = sqlnew();
		$prep = $conn->prepare("DELETE FROM $table WHERE account = ? AND  fingerprint = ?");
		$prep->bind_param("ss", $account, $fp);
		$prep->execute();

		/* check that we have properly stored and return the result */
		if (!certfp::is_certfp_already($account, $fp))
			return "You have deleted your certificate: $fp";

		return "Your certficate could not be deleted at this time";
	}
}
