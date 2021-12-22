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
	
	$raw = str_replace($parv[0]." ","",$u['msg']);
	$os->msg("#Services","RAW cmd from ".$u['nick'].": ".$u['msg']);
	$os->sendraw($raw);
});
