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
\\	Title: Dictionarahhhh
//	
\\	Desc: Give a dictionary lookup thingamajig
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class dictionary_lookup {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "dictionary_lookup";
	public $description = "Look up the definition of words.";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		/* We automatically clear up things attached to the module information, like AddServCmd();
		 * so don't worry!
		*/
	}


	function __init()
	{
	
		$help_string = "Looks up a word and prints the definition.";
		$syntax = "DEFINE <word>";
		$extended_help = 	"This command lets you look up a word and prints it on your status window.\n".
							"Some words are not available, as per the API that's used to lookup:\n".
							"https://dictionaryapi.dev/";

		if (!AddServCmd(
			'dictionary_lookup', /* Module name */
			'Dictionary', /* Client name */
			'DEFINE', /* Command */
			'dictionary_lookup::lookup', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		return true;
	}
	
	function lookup($u)
	{
		$parv = explode(" ",$u['msg']);

		if (strcasecmp($parv[0],"define") || !isset($parv[1]))
			return;

		$nick = $u['nick'];

		$str = mb_substr($u['msg'], strlen($parv[0]) + 1);
		$string = urlencode($str);

		$define = json_decode(file_get_contents("https://api.dictionaryapi.dev/api/v2/entries/en/$string"));

		if (!$define)
		{
			S2S("292 $nick->nick :** Could not find a definition for \"$str\"");
			return;
		}
	
		$d = $define[0];
		S2S("292 $nick->nick :** Showing the definition of \"$d->word\"");
		S2S("292 $nick->nick :- ");
		foreach($d->meanings as $i => $m)
		{
			S2S("292 $nick->nick :- $d->word");
			S2S("292 $nick->nick :- $m->partOfSpeech");
			S2S("292 $nick->nick :- ");
			foreach ($m->definitions as $definition)
				S2S("292 $nick->nick :-   $definition->definition");
			S2S("292 $nick->nick :- ");

		}
	}
}