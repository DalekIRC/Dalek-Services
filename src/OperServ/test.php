<?php

class test {

	public $name = "test";
	public $description = "test module";
	public $author = "Valware";
	public $version = "1.0";

    function __construct()
	{
		hook::func("raw", "cmd_test");
	}
	function __destruct()
	{
        global $elmer;
		hook::del("raw", "cmd_test");

        // clearup

	}
}

function cmd_test($u)
{
    
    return;
}
