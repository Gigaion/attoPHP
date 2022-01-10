<?php

	require_once('attoPHP.class.php');
	
	try {
		$attoPHP = new attoPHP();
		
		//Enable debug mode, outputs atto binary to a log file. Useful for auditing purposes.
		$attoPHP->debug = true;
		
		//When debug is enabled, you can change where the log outputs to. Defaults to ./atto-debug.log
		//echo "\n\n"."Setting debug log file location...\n";
		//$attoPHP->set_debug_log_file('logs/atto-debug.log');
		
		//Set binary location, if atto is not installed in a usr bin
		echo "\n\n"."Setting atto binary location...\n";
		$results = $attoPHP->set_atto_binary('/home/user/location/of/bin/atto');
		var_dump($results);
		
		//Set nano seed. Returns bool true/false if a valid 64 hex characters seed.
		//echo "\n\n"."Setting seed file...\n";
		//$results = $attoPHP->set_seed('/home/user/location/of/example-seed.txt', true);
		//var_dump($results);
		
		//Set nano seed. Returns bool true/false if a valid 64 hex characters seed.
		echo "\n\n"."Setting seed string...\n";
		$results = $attoPHP->set_seed('A6C409B11D5AB6B36B38326706D13ACECB818B13F51E259977D9BB26E8E86091');
		var_dump($results);
		
		//Show atto binary version.
		echo "\n\n"."Getting atto binary version...\n";
		$results = $attoPHP->atto_version();
		var_dump($results);
		
		//Generate and output a new seed.
		echo "\n\n"."Generating a new nano seed...\n";
		$results = $attoPHP->atto_new();
		var_dump($results);
		
		//Change or set account index (default is zero if unspecified).
		echo "\n\n"."Account index set to zero...\n";
		$results = $attoPHP->set_account(0);
		var_dump($results);
		
		//Get nano address using the set account index.
		echo "\n\n"."Getting nano account address...\n";
		$results = $attoPHP->atto_address();
		var_dump($results);
		
		//Get nano balance. If receivable nano is available, this command will also receive it.
		echo "\n\n"."Getting nano account balance...\n";
		$results = $attoPHP->atto_balance();
		var_dump($results);
		
		//Show nano string amount with less trailing zeroes.
		echo "\n\n"."Showing a more visually appealing nano amount (less trailing zeroes)...\n";
		$results = $attoPHP->visual_nano_amount('100.001');
		var_dump($results);

		//Convert nano to raw
		echo "\n\n"."Converting nano to raw...\n";
		$results = $attoPHP->nano_to_raw('100.001');
		var_dump($results);

		//Convert raw to nano
		echo "\n\n"."Converting raw to nano...\n";
		$results = $attoPHP->raw_to_nano('100001000000000000000000000000000');
		var_dump($results);
		
		//Validate nano amount represented as a string. bool false will be returned if invalid. If valid, a string of the amount will be returned.
		echo "\n\n"."Validating nano amount...\n";
		$results = $attoPHP->validate_nano_amount('0.0000003');
		var_dump($results);
		
		//Send nano to specified nano address.
		//echo "\n\n"."Sending nano amount...\n";
		//$results = $attoPHP->atto_send('0.1', 'nano_3xzdg86dmejx8h97jxkwq5rwpqtmpt3y6i3pqjz1gak5igrtwnue9bfmh1e7');
		//var_dump($results);
		
	}
	catch(TypeError $e) {
		var_dump($e->getMessage());
	}
	catch(Exception $e) {
		var_dump($e->getMessage());
	}
	
?>