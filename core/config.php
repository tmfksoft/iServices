<?php
// iServices Configuration Parser
// Why? Why not?
// You can use this function to load configs for your own modules.

function parse_config($conf,$req) {
	// Init some Vars.
	$config = array();
	$reqdat = array();
	$err = false;
	$errline = array();
	$inblock = false;
	$blockname = false;
	$debug = true;
	
	// Load our Files.
	$requirements = explode("\n",file_get_contents($req));
	$configdata = explode("\n",file_get_contents($conf));
	
	// Parse our config.
	if ($debug) { echo "Parsing Configuration File.\n"; }
	foreach ($configdata as $line => $item) {
		$line++;
		$item = trim($item);
		// Now our fun!
		if (isset($item[0]) && $item[0] != ";") {
			// Not comment, lets go.
			$dat = explode(" ",$item);
			if (isset($dat[1]) && ($dat[1] == "{")) {
				// Make a block.
				if (!$inblock) {
					$blockname = $dat[0];
					$config[$blockname] = array();
					$inblock = true; // We're in a block. EXPECT VALUES!
					// Even though we dont care about being in a block or not, A bracket mismatch could cause HAVOK!
				}
				else {
					// Whoa we're already in a block!
					die("Unexpected Start of Block at Line {$line} of {$conf}! Mismatched Brackets?\n");
				}
			}
			else if ($dat[0] == "}") {
				if ($inblock) {
					// End of a block.
					$inblock = false;
					$blockname = false;
				}
				else {
					die("Unexpected Block End Character at Line {$line} in {$conf}\n");
				}
			}
			else if (isset($dat[1]) && $dat[1] == "=") {
				// We've got a value. Lets get down to it.
				if ($blockname) {
					// We're in a block.
					// ATTENTION - DO VAL CHECK!$%&£^%$
					$config[$blockname][$dat[0]] = $dat[2]; // Set the value.
				}
				else {
					// We're outside any blocks.
					$config[$dat[0]] = $dat[2]; // Set the Value.
				}
			}
			else if ($item == "") {
				// It's a blank line. :(
			}
			else {
				// Whoa WTF are we parsing here?
				die("Unexpected Configuration Item '{$item}' at Line {$line} in {$conf}\n");
			}
		}
		else {
			if ($debug) {
				if (isset($item[0])) { echo "Line {$line}, is a comment. Ignoring.\n"; }
				else { echo "Line {$line}, is a blank line. Ignoring.\n"; }
			}
		}
	}
	if ($inblock) {
		die("Expected end of Block '{$blockname}' in {$conf}\n");
	}
	if ($debug) { var_dump($config); }
	// Now we've sorted the config out, We need to cycle the Requirements file.
	$blockname = false; // Reset our block system Even though we should have taken care of it already.
	if ($debug) { echo "Parsing Requirements File.\n"; }
	// Essentially we're building a Config 'Model' if our parsed config doesnt meet this criteria we
	// throw our toys out of the pram.
	foreach ($requirements as $line => $item) {
		// Sort some initial stuff.
		$item = trim($item);
		$line++;
		$dat = explode(" ",$item);
		if (isset($item[0]) && $item[0] != ";") {
			// Not a Comment.
			if ($dat[0][0] == "@") {
				// Block REQBLOCK
				$blockname = substr($dat[0],1);
				$reqdat[$blockname] = array();
				$inblock = true;
			}
			else if ($dat[0][0] == "#") {
				// Block REVAL
				$value = substr($dat[0],1);
				$type = false;
				if (isset($dat[1])) {
					$type = $dat[1];
				}
				if ($inblock) {
					// You can set a global Requirement Item before ALL Block Requirement Items.
					$reqdat[$blockname][$value] = $type;
				}
				else {
					$reqdat[$value] = $type;
				}
			}
			else {
				die("Unexpected Requirements Item '{$item}' at Line {$line} in {$req}\n");
			}
		}
		else {
			if ($debug) {
				if (isset($item[0])) {
					echo "Line {$line}, is a comment. Ignoring.\n";
				}
				else {
					echo "Line {$line}, is a blank line. Ignoring.\n";
				}
			}
		}
	}
	if ($debug) { var_dump($reqdat); }
	
	// Now we've got everything we need to make a move on checking
	// that our Parsed config meets our requirements.
	$inblock = false;
	$blockname = false;
	foreach ($reqdat as $key => $value) {
		// Everything is pretty much a simple comparison system.
		echo "Checking config for $key where its equal to $value";
		if (is_array($value)) {
			$inblock = true;
			$blockname = $key;
		}
		if ($inblock) {
			// We're checking a block
			//if (isset($config[$value
			// I've got to this point and I dont actually know what the fuck Im doing.
		}
		else {
			// We're checking a global Var Space. :o
			if (isset($config[$key])) {
				if ($value == "int") {
					$confval = $config[$key];
					if (!is_numeric($conval)) {
						$err = true;
						$errline[] = "Expected Interger for '{$key'} in GLOBAL Config.";
					}
				}
				else {
					// Its being assumed as text.
				}
			}
			else {
				$err = true;
				$errline[] = "Missing GLOBAL value '{$key}' in {$conf}.";
			}
		}
	}
	if ($err) {
		echo count($errline)." Errors were found when reading {$conf}.\n";
		foreach ($errline as $line) {
			echo "\t- ".$line."\n";
		}
		die();
	}
	return $config;
}

// Usage: parse_config("config.cfg","config.req");

$data = parse_config("../data/config.cfg","../data/config.req");
die();
?>