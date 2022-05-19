<?php

hook::func("privmsg", function($u)
{
	global $os;

	$parv = explode(" ",$u['parv']);

	if ($parv[0] !== "!modload")
		return;
	loadmodule($parv[1]);
});

hook::func("privmsg", function($u)
{
	global $os;

	$parv = explode(" ",$u['parv']);

	if ($parv[0] !== "!modunload")
		return;
	unloadmodule($parv[1]);
});
hook::func("privmsg", function($u)
{
	global $os,$modules;

	$parv = explode(" ",$u['parv']);

	if ($parv[0] !== "!modlist")
		return;
	foreach($modules as $m)
	{
		$official = (isset($m->official) && $m->official == true) ? "Yes" : "No";
		$os->notice($u['nick'],"Name: $m->name - Desc: $m->description - Version: $m->version - Author: $m->author - Official: $official");
	}
});