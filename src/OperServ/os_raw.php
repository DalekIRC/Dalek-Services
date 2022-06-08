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
\\	Title: RAW
//	
\\	Desc: Raw commands lmao
//	
\\	
//	
\\	
//	
\\	Version: 1
//				
\\	Author:	Valware
//				
*/


operserv::func("privmsg", function($u){

	global $os,$operserv;

	
	$parv = explode(" ",$u['msg']);
	if ($parv[0] !== "raw"){ return; }
	var_dump($u['msg']);
	
	$raw = mb_substr($u['msg'],strlen($parv[0]) + 1);
	var_dump($raw);
	$os->msg("#Services","RAW cmd from ".$u['nick'].": $raw");
	$os->sendraw($raw);
});
