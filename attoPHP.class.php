<?php
	
	//***************************************************************
	//************* :::::: attoPHP :::::: ***************************
	//*********** Nano atto wallet binary to PHP bridge. ************
	//***************************************************************
	//***************************************************************
	//**************** :: Dependencies :: ***************************
	//***     - atto binary : https://github.com/codesoap/atto    ***
	//***     - PHP Library bcmath : apt install php-bcmath       ***
	//***     - Script tested on : PHP 7.4+                       ***
	//***************************************************************
	//***************************************************************
	
	//MIT License
	
	//Copyright (c) 2022 Gigaion, LLC.
	//
	//Permission is hereby granted, free of charge, to any person obtaining a copy
	//of this software and associated documentation files (the "Software"), to deal
	//in the Software without restriction, including without limitation the rights
	//to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	//copies of the Software, and to permit persons to whom the Software is
	//furnished to do so, subject to the following conditions:
	//
	//The above copyright notice and this permission notice shall be included in all
	//copies or substantial portions of the Software.
	//
	//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	//IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	//FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	//AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	//LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	//OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
	//SOFTWARE.
	
	
	class attoPHP {
		
		private string $seed = '';
		private int $account = 0; //Default account index 0
		public bool $debug = false;
		private string $attoBinary = 'atto';
		private string $debugLogFile = 'atto-debug.log';
		
		//================================================
		//==============General Utilities=================
		//================================================
		
		//Check if a string starts with a specific value
		private function str_starts_with(string $string, string $startString) {
			return (substr($string, 0, strlen($startString)) === $startString);
		}
		
		//Validate that a nano address visually looks correct.
		//This can be later improved upon with better cryptography validation.
		public function validate_nano_address(string $address) {
			if($this->str_starts_with($address,  'nano_')) {
				if(strlen($address) == 65) {
					if(ctype_alnum(str_replace('nano_', '', $address))) {
						return true;
					}
				}
			}
			return false;
		}
		
		//Validate nano string amount contains correct decimal length requirements.
		//Note: Can be later improved to better accomedate raw validation (raw does not have decimals).
		public function validate_nano_amount(string $amount) {
			$pattern = '/^[+-]?[0-9]*(\.[0-9]*)?$/';
			
			//Check initial input amount
			if(preg_match($pattern, $amount)) {
				
				//Verify its a proper number by adding zero
				$amountCheck = bcadd($amount, 0, 30);
				if(preg_match($pattern, $amountCheck)) {
					return $amountCheck;
				}
				else {
					return false;
				}
			}
			return false;
		}
		
		//Remove trailing zeroes from decimal string to make it more visually appealing
		//https://stackoverflow.com/questions/5149129/how-to-strip-trailing-zeros-in-php
		public function visual_nano_amount(string $amount) {
			$amount = $this->validate_nano_amount($amount);
			
			if($amount) {
				if(strpos($amount,".") !== false) {
					$visualAmount = rtrim($amount, "0");
				}
				else {
					$visualAmount = $amount;
				}
				
				$visualAmount = rtrim($visualAmount,".");
				return $visualAmount;
			}
			else {
				return false;
			}
		}
		
		//bcmath: Subtract first $amount1 from $amount2
		public function nano_math_subtract(string $amount1, string $amount2) {
			$amount1 = $this->validate_nano_amount($amount1);
			$amount2 = $this->validate_nano_amount($amount2);
			
			if($amount1 && $amount2) {
				$nanoMath = bcsub($amount1, $amount2, 30);
				return $nanoMath;
			}
			else {
				return false;
			}
		}
		
		//Convert nano to raw
		public function nano_to_raw(string $amount) {
			$amount = $this->validate_nano_amount($amount);
			
			if($amount) {
				$nano_to_raw = bcmul('1000000000000000000000000000000', $amount, 0);
				return $nano_to_raw;
			}
			else {
				return false;
			}
		}
		
		//Convert raw to nano
		public function raw_to_nano(string $amount) {
			$amount = $this->validate_nano_amount($amount);
			
			if($amount) {
				$raw_to_nano = bcdiv($amount, '1000000000000000000000000000000', 30);
				return $raw_to_nano;
			}
			else {
				return false;
			}
		}
		
		//================================================
		//=============Errors and logging=================
		//================================================
		
		//Capture intended error and throw it
		private function error(string $message) {
			throw new Exception('[Exception]: '.$message);
		}
		
		public function set_debug_log_file(string $debugLogFile) {
			if($debugLogFile != '') {
				//Check if debug log file exists. Attempt to create it, if it does not exist.
				if(!file_exists($debugLogFile)) {
					if(!touch($debugLogFile)) {
						$this->error('Unable to create blank debug log file. Check that directory exists and location is writable.');
						return false;
					}
				}
				
				//Check if debug file is writeable
				if(is_writable($debugLogFile)) {
					$this->debugLogFile = $debugLogFile;
					return true;
				}
				else {
					$this->error('Debug log file is not writeable. Check that file location is writable.');
					return false;
				}
			}
			else {
				$this->error('Unable to set debug log file. Log file name was empty.');
				return false;
			}
		}
		
		//Trace and log actions
		private function trace($msg='') {			
			if($this->debug) {
				$m = '';
				$pre1 = "\033[33m";
				$pre2 = "\033[33;1m";
				$pre3 = "\033[1m \033[37m";
				$m = $pre1."[TRACE] ".$pre2."[".date("H:i:s")."] ".$pre3.$msg."\n";
				
				//echo($m);
				$debugLog = file_put_contents($this->debugLogFile, "[".date("M, d y H:i:s")."] -> ".$msg.PHP_EOL, FILE_APPEND | LOCK_EX);
			}
		}
		
		//================================================
		//==============Execute/Run atto==================
		//================================================
		
		//Execute atto commands with seed specified
		private function execute(string $command) {
			if($command != '') {
				if($this->seed != '') {
					$results = shell_exec('echo "'.$this->seed.'" | '.$this->attoBinary.' '.trim($command).' 2>&1');
					$this->trace("[Command]: ".trim($command)." [Results]: ".json_encode($results));
					
					$validateResults = $this->is_valid_results($results);
					if($validateResults->validBool) {
						return trim($results);
					}
					else {
						$this->error($validateResults->message);
						return false;
					}
				}
				else {
					$this->error('No seed provided.');
					return false;
				}
			}
			else {
				$this->error('No command provided to execute.');
				return false;
			}
		}
		
		//Execute atto commands without seed specified
		private function execute_plain(string $command) {
			if($command != '') {
				$results = shell_exec($this->attoBinary.' '.trim($command));
				$validateResults = $this->is_valid_results($results);
				if($validateResults->validBool) {
					return trim($results);
				}
				else {
					$this->error($validateResults->message);
					return false;
				}
			}
			else {
				return false; //No command provided to execute
			}
		}
		
		//Based on known or provided error array, return if executed results are valid.
		private function is_valid_results(string $results, array $attoErrorArray = array()) {
			$attoErrorGenericArray = array(
				'Error: account has not yet been opened' => 'Insufficient balance. Sending account has not yet been opened.',
				'Usage:' => 'Invalid atto command.',
				'Error: could not parse seed' => 'Invalid seed provided.',
				'Error: EOF' => 'Error: EOF',
				'Creating send block... Error: could not publish block: Block is invalid' => 'Failed to send nano. Invalid block or send balance.'
			);
			
			$attoErrorArray = array_merge($attoErrorGenericArray, $attoErrorArray);
			
			$errorResult = '';
			$errorBool = true;
			foreach($attoErrorArray as $attoError => $errorMessage) {
				if(stristr($results, $attoError)) {
					$errorResult = $errorMessage;
					$errorBool = false;
				}
			}
			
			return (object) array(
				'validBool' => $errorBool,
				'message' => $errorResult
			);
		}
		
		//Only return results of executed command if results are valid.
		private function return_valid_results(string $results) {
			
			$checkResults = $this->is_valid_results($results);
			if($checkResults->validBool) {
				return $results;
			}
			else {
				$this->error($checkResults->message);
				return false;
			}
		}
		
		//================================================
		//==================Set Variables=================
		//================================================
		
		//Set atto binary (defaults to installed command call atto if not set)
		public function set_atto_binary(string $attoBinary) {
			$attoBinary = trim($attoBinary);
			if(file_exists($attoBinary)) {
				if(is_executable($attoBinary)) {
					$this->attoBinary = $attoBinary;
					return true;
				}
				else {
					$this->error('Atto binary not executable. Please update binary file execution permissions.');
					return false;
				}
			}
			else {
				$this->error('Atto binary not found.');
				return false;
			}
		}

		//Set seed (64 character hex)
		public function set_seed(string $seed, bool $localFile = false) {
			
			if($localFile) {
				$seed = file_get_contents($seed, true);
			}
			
			$seed = strtoupper(trim($seed));
			
			if(ctype_alnum($seed)) {
				if(strlen($seed) == 64) {
					if(ctype_xdigit($seed)) {
						$this->seed = $seed;
						return true;
					}
				}
			}
			
			$this->error('Invalid seed provided. Seed must be 64 hex characters.');
			return false;
		}
		
		//General note: int cast will remove decimals: 1.8 to 1
		//account index must be in this range: 0 and 4,294,967,295 (per atto binary documentation)
		public function set_account(int $account) {
			if($account >= 0 && floor($account) == $account) {
				if(4294967295 >= floor($account)) {
					$this->account = $account;
					return true;
				}
				else {
					$this->error('Invalid account index integer must be 0 through 4,294,967,295.');
					return false;
				}
			}
			else {
				$this->error('Invalid account index integer.');
				return false;
			}
		}
		
		//================================================
		//==================Atto Commands=================
		//================================================
		
		//atto -v
		public function atto_version() {
			$command = '-v';
			$results = $this->execute_plain($command);
			
			return $this->return_valid_results($results);
		}
		
		//atto n[ew]
		public function atto_new() {
			$command = 'new';
			$results = $this->execute_plain($command);
			
			return $this->return_valid_results($results);
		}

		//atto [-a ACCOUNT_INDEX] a[ddress]
		public function atto_address() {
			$command = '-a '.$this->account.' address';
			$results = $this->execute($command);
			
			return $this->return_valid_results($results);
		}

		//atto [-a ACCOUNT_INDEX] b[alance]
		public function atto_balance() {
			$command = '-a '.$this->account.' balance';
			$results = $this->execute($command);
			
			$balance = $this->return_valid_results($results);
			
			if(stristr($balance, 'Creating receive block for')) {
				$balanceReceive = explode("\n", $balance);
				$balance = end($balanceReceive);
				
				if(stristr($balance, 'Error: received unexpected')) {
					$balance = false;
					$this->error('Server error. Unable to create receive block while checking balance. Please check work server.');
					return false;
				}
			}
			
			$balance = str_replace(' NANO', '', $balance);
			$balance = $this->validate_nano_amount($balance);
			
			if($balance) {
				return $balance;
			}
			else {
				$this->error('Server error. Invalid nano amount returned.');
				return false;
			}
		}

		//atto [-a ACCOUNT_INDEX] b[alance]
		public function atto_representative(string $representative) {
			if($this->validate_nano_address($representative)) {
				$command = '-a '.$this->account.' representative '.$representative;
				$results = $this->execute($command);
				
				return $this->return_valid_results($results);
				
			}
			else {
				$this->error('Invalid nano address provided.');
				return false;
			}
		}
		
		//atto [-a ACCOUNT_INDEX] [-y] s[end] AMOUNT RECEIVER
		public function atto_send(string $amount, string $receiver) {
			if($this->validate_nano_address($receiver)) {
				if($this->validate_nano_amount($amount)) {
					$command = '-a '.$this->account.' -y send '.$amount.' '.$receiver;
					
					$results = $this->execute($command);
					
					$validateResults = $this->is_valid_results($results);
					if($validateResults->validBool) {
						
						if(stristr($results, 'Creating send block... done')) {
							return true;
						}
						else {
							$this->error('Failed to send nano.');
							return false;
						}
					}
					else {
						$this->error($validateResults->message);
						return false;
					}
				}
				else {
					$this->error('Invalid nano amount provided. Amount must be a number represented as a string.');
					return false;
				}
			}
			else {
				$this->error('Invalid nano address provided.');
				return false;
			}
		}
	}
?>
