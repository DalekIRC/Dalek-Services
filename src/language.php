<?php

function IRC($string){
	
	$eval = $string ?? NULL;
	global $language;

	if (!isset($language['NAME']) || !$language['NAME']){
		die("Included language file has no defined 'NAME'");
	}

	if (!$eval){ $eval = "ERR_NOSUCHSTRING"; }
	
	if (!isset($language[$eval])){
		
		echo "Warning. Returned error ERR_NOSUCHSTRING for $string \n";
		
		$eval = "ERR_NOSUCHSTRING";
		return $language[$eval];
		
	}
	else { return $language[$string]; }
	
}

