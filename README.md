# attoPHP
attoPHP. Nano atto wallet binary to PHP bridge.

### Features

atto wallet:
- Interact with the atto Nano wallet binary through PHP. 
- atto wallet commands as PHP functions.

### Dependencies
- atto binary : https://github.com/codesoap/atto
- PHP Library bcmath : apt install php-bcmath
- PHP 7.4 (Tested on PHP 7.4, may work on other versions)
- Tested on Linux

### Note
attoPHP uses shell_exec() to call the atto binary!

Even with our existing validation and sanitation methods, for best practice extreme caution should be used when providing function parameters to this class.
With proper care this class can be used succesfully, although audits, modifications, and testing may be necessary before using this in a production setting.

------------

**Initiate attoPHP class and specify binary:**

set_atto_binary() optional if binary is callable on your machine directly.

	$attoPHP = new attoPHP();
	$attoPHP->set_atto_binary('/home/user/location/of/bin/atto')

**Set the 64 hex character seed:**

	Two methods to specify seed (file or string):
	$attoPHP->set_seed(SEED)
	$attoPHP->set_seed('/home/user/location/of/example-seed.txt', true)

**Optionally change your account index (Defaults to 0)**

	$attoPHP->set_account(INDEX);

**Then use the functions:**

	$attoPHP->atto_address()
	$attoPHP->atto_balance()
	$attoPHP->atto_representative(REPRESENTATIVE)
	$attoPHP->atto_send(AMOUNT, RECEIVER)

------------
### Utilities
Validate that a nano address visually looks correct:

`$attoPHP->validate_nano_address(ADDRESS)`

Validate nano string amount contains correct decimal length requirements:

`$attoPHP->validate_nano_amount(AMOUNT)`

Remove trailing zeroes from decimal string to make it more visually appealing:

`$attoPHP->visual_nano_amount(AMOUNT)`

bcmath: Subtract first $amount1 (string) from $amount2 (string):

`$attoPHP->nano_math_subtract(AMOUNT1, AMOUNT2)`

Convert nano to raw (string):

`$attoPHP->nano_to_raw(AMOUNT)`

Convert raw to nano (string):

`$attoPHP->raw_to_nano(AMOUNT)`

------------

Please note we are not a cryptographer. 

If you find any bugs we recommend putting in a pull request. 

Any additional updates and features are always welcome from the open source community to improve this project.
