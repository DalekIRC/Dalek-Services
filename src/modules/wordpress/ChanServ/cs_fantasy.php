<?php
/*
 *	(C) 2022 DalekIRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: Implements !fantasy commands
 *
 * 
 *	Version: 1
*/

hook::func("privmsg", function($u)
{
	$tok = explode(" ",$u['parv']);
	if ($u['dest'][0] !== "#")
		return;
	$params = $u['dest'].rparv($u['parv']);
	$tok1 = explode(" ",$params);
	if (!isset($tok1[1]))
		$params = "";

	if ($tok[0][0] == "!")
	{
		$command = str_replace("!","",$tok[0]);
		chanserv::run("privmsg", array(
			'msg' => "$command $params",
			'nick' => $u['nick'])
		);
	}
});