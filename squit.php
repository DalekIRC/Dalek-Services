<?php

hook::func("squit", function($u)
{
	squit($u['sid']);
});


function squit($sid)
{
	global $ns;
	/* loop through the server and attached servers */
	$r = recurse_serv_attach($sid);
	$s = find_serv($sid);
	$l = count($r) - 1;
	$ns->msg("#valerie_login","SQUIT: Removing information about 4".$s['servername']." and ".$l." servers beyond.");
	foreach ($r as $v)
		del_sid($v);
}

function recurse_serv_attach($sid)
{
	$squit = array();
	for ($squit[] = $sid, $i = 0; isset($squit[$i]); $i++)
		foreach(serv_attach($squit[$i]) as $key => $value)
			if (!in_array($value,$squit))
				$squit[] = $value;

	return $squit;
}

function del_sid($sid)
{
	global $sql;
	$sql->delsid($sid);
}

function serv_num_users($sid)
{
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_user WHERE SID = ?");
	$prep->bind_param("s",$sid);
	$prep->execute();
	$result = $prep->get_result();
	$return = $result->num_rows;
	return $return;
}

function serv_num_attach($sid)
{
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_server WHERE intro_by = ?");
	$prep->bind_param("s",$sid);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result)
		return 0;
	if (($numr = $result->num_rows) == 0)
		return 0;
	else
		return $numr;
}

function serv_attach($sid)
{
	$return = array();
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_server WHERE intro_by = ?");
	$prep->bind_param("s",$sid);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result)
		return 0;
	while ($row = $result->fetch_assoc())
		if (!in_array($row['sid'],$return))
			$return[] = $row['sid'];

	return $return;
}

function recurse_serv_users($sid)
{
	$return = array();
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_user WHERE SID = ?");
	foreach (recurse_serv_attach($sid) as $s)
	{
		$prep->bind_param("s",$s);
		$prep->execute();
		$result = $prep->get_result();
		if (!$result)
			continue;
		while ($row = $result->fetch_assoc())
			if (!in_array($row['UID'],$return))
				$return[] = $row['UID'];
	}
	return $return;
}


