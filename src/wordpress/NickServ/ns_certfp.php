<?php

nickserv::func("privmsg", function($u)
{
	global $ns;

	$nick = new User($u['nick']);
	$parv = explode(" ",$u['msg']);
	
	if ($parv[0] !== "certfp")
		return;
	
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
			$ns->notice($nick->uid,"You do not have a certfp to store.");
			return;
		}

		$ns->notice($nick->uid, add_certfp($nick->ip,$nick->account,$nick->meta->certfp));
	}

	elseif (strtolower($parv[1]) == "del")
	{
		if (!isset($parv[2]))
		{
			$ns->notice($nick->uid,"You did not specify a fingerprint to delete.");
			return;
		}
		$ns->notice($nick->uid, del_certfp($nick->ip,$nick->account,$nick->meta->certfp));
	}
	elseif (strtolower($parv[1]) == "list")
	{
		$table = sqlprefix().'fingerprints_external';
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
			

});

function is_certfp_already($ip, $account, $fp) : bool
{
	$table = sqlprefix().'fingerprints_external';
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
	if (is_certfp_already($ip, $account, $fp))
		return "You already have that certfp saved.";

	/* put to the table */
	$table = sqlprefix().'fingerprints_external';
	$conn = sqlnew();
	$prep = $conn->prepare("INSERT INTO $table (account, ip, fingerprint) VALUES (?, ?, ?)");
	$prep->bind_param("sss", $account, $ip, $fp);
	$prep->execute();

	/* check that we have properly stored and return the result */
	if (is_certfp_already($ip, $account, $fp))
		return "You have added your certificate: $fp";

	return "Your certficate could not be added at this time";
}

function del_certfp($ip, $account, $fp) : string
{

	/* already has this cert saved */
	if (!is_certfp_already($ip, $account, $fp))
		return "Couldn't find saved certfp: $fp";

	/* delete from the table */
	$table = sqlprefix().'fingerprints_external';
	$conn = sqlnew();
	$prep = $conn->prepare("DELETE FROM $table WHERE account = ? AND ip = ? AND  fingerprint = ?");
	$prep->bind_param("sss", $account, $ip, $fp);
	$prep->execute();

	/* check that we have properly stored and return the result */
	if (!is_certfp_already($ip, $account, $fp))
		return "You have deleted your certificate: $fp";

	return "Your certficate could not be deleted at this time";
}



nickserv::func("helplist", function($u){
	
	global $ns;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"CERTFP              Modifies or displays the certificate list for your nick.");
	
});



nickserv::func("help", function($u){
	
	global $ns;
	
	if ($u['key'] !== "certfp"){ return; }
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"Command: CERTFP");
	$ns->notice($nick,"Syntax: /msg $ns->nick CERTFP ADD");
	$ns->notice($nick,"             /msg $ns->nick CERTFP DEL <certfp>");
	$ns->notice($nick," ");
	$ns->notice($nick,"Example: /msg $ns->nick CERTFP ADD");
});
