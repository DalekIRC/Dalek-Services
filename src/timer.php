<?php
function track_time($ourtime)
{
	global $tracktime;
	if (!isset($tracktime))
		$tracktime = $ourtime;
	if ($ourtime !== $tracktime)
	{
		$tracktime = $ourtime;
		hook::run("timer", array());
		hook::run("rtimer", array());
	}
	return true;
}

hook::func("preconnect", function()
{
	$conn = sqlnew();
	$prep = $conn->prepare("CREATE TABLE IF NOT EXISTS dalek_timer (
		id int AUTO_INCREMENT NOT NULL,
		time int NOT NULL,
		raw varchar(255) NOT NULL,
		PRIMARY KEY(id)
	)");
	$prep->execute();
	$prep = $conn->prepare("CREATE TABLE IF NOT EXISTS dalek_rtimer (
		id int AUTO_INCREMENT NOT NULL,
		time int NOT NULL,
		raw varchar(255) NOT NULL,
		PRIMARY KEY(id)
	)");
	$prep->execute();
	$prep->close();
});
function timer_add(int $sec,$raw)
{
	global $ns;
	$conn = sqlnew();
	$prep = $conn->prepare("INSERT INTO dalek_timer (time, raw) VALUES (?,?)");
	$timer = ourtime() + $sec;
	$prep->bind_param("is",$timer,$raw);
	$prep->execute();
	$prep->close();

	if (isset($ns))
		$ns->msg("#valerie_login","raw timer added for $raw");
}
function rtimer_add(int $sec,$raw)
{

	$conn = sqlnew();
	$prep = $conn->prepare("INSERT INTO dalek_rtimer (time, raw) VALUES (?,?)");
	$timer = ourtime() + $sec;
	$prep->bind_param("is",$timer,$raw);
	$prep->execute();
	$prep->close();

}
hook::func("timer", function($u)
{
	global $tracktime,$serv;
	if (!isset($serv))
		return;
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_timer WHERE time <= ?");
	$prep->bind_param("i",$tracktime);
	$prep->execute();

	$result = $prep->get_result();
	if (!$result)
		return;
	while ($row = $result->fetch_assoc())
	{
		$serv->Send($row['raw']);
	}
	$prep->close();

	$prep = $conn->prepare("DELETE FROM dalek_timer WHERE time <= ?");
	$prep->bind_param("i",$tracktime);
	$prep->execute();
	$prep->close();
});
hook::func("rtimer", function($u)
{
	global $tracktime,$serv;
	if (!isset($serv))
		return;
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_rtimer WHERE time <= ?");
	$prep->bind_param("i",$tracktime);
	$prep->execute();

	$result = $prep->get_result();
	if (!$result)
		return;
	while ($row = $result->fetch_assoc())
	{
		if ($row['raw'] == "ping")
			massping();
	}
	$prep->close();

	$prep = $conn->prepare("DELETE FROM dalek_rtimer WHERE time <= ?");
	$prep->bind_param("i",$tracktime);
	$prep->execute();
	$prep->close();
});


function ping_uplink()
{
	global $serv,$cf;
	
	$serv->Send("PING :piss.pathweb.org");
	rtimer_add(60,"ping");

	$conn->close();

}
