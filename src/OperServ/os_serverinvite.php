<?php

/* invitation table */

hook::func("preconnect", function()
{
	$conn = sqlnew();
	$conn->query("CREATE TABLE IF NOT EXISTS dalek_invite (
				id int NOT NULL AUTO_INCREMENT,
				code varchar(255) NOT NULL,
				timestamp varchar(255) NOT NULL,
				realtime int NOT NULL,
				PRIMARY KEY(id)
	)");
	$conn->close();
});


/* check invitation credentials */

function is_invite($one, $two) : bool
{
	global $servertime;

	$return = false;

	$conn = sqlnew();

	/* quickly clear up any expired invitations (12hrs) */

	$exptime = $servertime - 43200;
	$conn->query("DELETE FROM dalek_invite WHERE realtime < $exptime");

	/* check their credentials */ 
	$prep = $conn->prepare("SELECT * FROM dalek_invite WHERE timestamp = ?");
	$prep->bind_param("s",$one);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result || $result->num_rows == 0)
	{
		$prep->close();
		return false;
	}
	while ($row = $result->fetch_assoc())
		if ($row['code'] == $two)
			$return = true;

	if ($return)
	{
		$prep = $conn->prepare("DELETE FROM dalek_invite WHERE code = ?");
		$prep->bind_param("s",$two);
		$prep->execute();
	}	
	$prep->close();
	return $return;
}



/* Command for generating an invite */
operserv::func("privmsg", function($u)
{
	global $os;

	$parv = explode(" ",$u['msg']);
	
	if ($parv[0] !== "serverinvite")
		return;

	$nick = new User($u['nick']);

	if (strpos($nick->usermode,"o") == false)
		return;

	$invite = generate_invite_code();
	$code = explode(":",$invite);

	$os->notice($nick->nick,"Server invitation has been generated. Expiry: 12hrs. User: ".$code[0]."   - Password: ".$code[1]);
});


/* actually generate the invite code */

function generate_invite_code()
{
	global $servertime;

	$conn = sqlnew();

	$ts = md5($servertime);
	
	$invite = "";

	/* generate some random ascii 40 chars long */
	for ($i = 0; strlen($invite) !== 40; $i++)
	{
		$r = rand(32,126);
		$invite .= chr($r);
	}
	
	/* hash it in, lets say, sha512 */
	$hash = hash("sha512",$invite);

	/* put to table */
	$prep = $conn->prepare("INSERT INTO dalek_invite (code,timestamp,realtime) VALUES (?,?,?)");
	$prep->bind_param("ssi",$hash,$ts,$servertime);
	$prep->execute();

	return $ts.":".$hash;
}