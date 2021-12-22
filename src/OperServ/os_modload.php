<?php

hook::func("privmsg", function($u)
{
	global $os;

	$parv = explode(" ",$u['parv']);

	if ($parv[0] !== "!modload")
		return;
	loadmodule($parv[1]);
});