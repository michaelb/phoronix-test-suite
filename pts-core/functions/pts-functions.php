<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions.php: General functions required for Phoronix Test Suite operation.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// Start Phoronix Test Suite
require_once("pts-core/functions/pts.php");
require_once("pts-core/functions/pts-init.php");

// Load Main Functions
require_once("pts-core/functions/pts-interfaces.php");
require_once("pts-core/functions/pts-functions_config.php");
require_once("pts-core/functions/pts-functions_system.php");
require_once("pts-core/functions/pts-functions_tests.php");
require_once("pts-core/functions/pts-functions_types.php");
require_once("pts-core/functions/pts-functions_modules.php");

if(IS_SCTP_MODE)
	require_once("pts-core/functions/pts-functions-sctp.php");

// User's home directory for storing results, module files, test installations, etc.
define("PTS_USER_DIR", pts_user_home() . ".phoronix-test-suite/");

// Distribution External Dependency Locations
define("XML_DISTRO_DIR", PTS_DIR . "pts/distro-xml/");
define("SCRIPT_DISTRO_DIR", PTS_DIR . "pts/distro-scripts/");

// Misc Locations
define("ETC_DIR", PTS_DIR . "pts/etc/");
define("MODULE_DIR", PTS_DIR . "pts-core/modules/");
define("RESULTS_VIEWER_DIR", PTS_DIR . "pts-core/pts-results-viewer/");
define("FONT_DIR", RESULTS_VIEWER_DIR . "fonts/");

// Test & Suite Locations
define("XML_PROFILE_DIR", PTS_DIR . "pts/test-profiles/");
define("XML_PROFILE_CTP_BASE_DIR", XML_PROFILE_DIR . "base/");
define("XML_SUITE_DIR", PTS_DIR . "pts/test-suites/");
define("TEST_RESOURCE_DIR", PTS_DIR . "pts/test-resources/");
define("TEST_RESOURCE_CTP_BASE_DIR", TEST_RESOURCE_DIR . "base/");
define("XML_PROFILE_LOCAL_DIR", PTS_USER_DIR . "test-profiles/");
define("XML_SUITE_LOCAL_DIR", PTS_USER_DIR . "test-suites/");
define("TEST_RESOURCE_LOCAL_DIR", PTS_USER_DIR . "test-resources/");

pts_config_init();
define("TEST_ENV_DIR", pts_find_home(pts_read_user_config(P_OPTION_TEST_ENVIRONMENT, "~/.phoronix-test-suite/installed-tests/")));
define("SAVE_RESULTS_DIR", pts_find_home(pts_read_user_config(P_OPTION_RESULTS_DIRECTORY, "~/.phoronix-test-suite/test-results/")));
define("PTS_DOWNLOAD_CACHE_DIR", pts_find_home(pts_download_cache()));
pts_extended_init();

// Register PTS Process
if(pts_process_active("phoronix-test-suite"))
{
	echo pts_string_header("WARNING: It appears that the Phoronix Test Suite is already running...\nFor proper results, only run one instance at a time.");
}
pts_process_register("phoronix-test-suite");
register_shutdown_function("pts_shutdown");

// Etc
$PTS_GLOBAL_ID = 1;

// PTS Modules Support
if(function_exists("pts_module_start_process"))
	pts_module_start_process();

// Phoronix Test Suite - Functions
function pts_copy($from, $to)
{
	// Copy file
	if(!is_file($to) || md5_file($from) != md5_file($to))
		copy($from, $to);
}
function pts_gd_available()
{
	// Checks if PHP GD library is available for image rendering

	if(!extension_loaded("gd"))
	{
	/*	if(dl("gd.so"))
		{
			$gd_available = true;
		}
		else	*/
			$gd_available = false;
			echo "\nThe PHP GD extension must be loaded in order for the graphs to display!\n";
	}
	else
		$gd_available = true;

	return $gd_available;
}
function pts_save_result($save_to = null, $save_results = null)
{
	// Saves PTS result file
	if(strpos($save_to, ".xml") === FALSE)
	{
		$save_to .= ".xml";
	}

	$save_to_dir = dirname(SAVE_RESULTS_DIR . $save_to);

	if(!is_dir(SAVE_RESULTS_DIR))
		mkdir(SAVE_RESULTS_DIR);
	if($save_to_dir != '.' && !is_dir($save_to_dir))
		mkdir($save_to_dir);

	if(!is_dir(SAVE_RESULTS_DIR . "pts-results-viewer"))
	{
		mkdir(SAVE_RESULTS_DIR . "pts-results-viewer");
	}

	pts_copy(RESULTS_VIEWER_DIR . "pts.js", SAVE_RESULTS_DIR . "pts-results-viewer/pts.js");
	pts_copy(RESULTS_VIEWER_DIR . "pts-results-viewer.xsl", SAVE_RESULTS_DIR . "pts-results-viewer/pts-results-viewer.xsl");
	pts_copy(RESULTS_VIEWER_DIR . "pts-viewer.css", SAVE_RESULTS_DIR . "pts-results-viewer/pts-viewer.css");
	pts_copy(RESULTS_VIEWER_DIR . "pts-logo.png", SAVE_RESULTS_DIR . "pts-results-viewer/pts-logo.png");

	if(!is_file($save_to_dir . "/pts-results-viewer.xsl") && !is_link($save_to_dir . "/pts-results-viewer.xsl"))
		link(SAVE_RESULTS_DIR . "pts-results-viewer/pts-results-viewer.xsl", $save_to_dir . "/pts-results-viewer.xsl");
	
	if($save_to == null || $save_results == null)
		$bool = true;
	else
	{
		$save_name = basename($save_to, ".xml");

		if($save_name == "composite")
		{
			if(pts_gd_available())
			{
				if(!is_dir($save_to_dir . "/result-graphs"))
				{
					mkdir($save_to_dir . "/result-graphs");
				}

				$xml_reader = new tandem_XmlReader($save_results);
				$results_name = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_TITLE);
				$results_version = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_VERSION);
				$results_attributes = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
				$results_scale = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_SCALE);
				$results_proportion = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_PROPORTION);
				$results_result_format = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_RESULTFORMAT);
				$results_raw = $xml_reader->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);

				$results_identifiers = array();
				$results_values = array();

				foreach($results_raw as $result_raw)
				{
					$xml_results = new tandem_XmlReader($result_raw);
					array_push($results_identifiers, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER));
					array_push($results_values, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_VALUE));
				}

				for($i = 0; $i < count($results_name); $i++)
				{
					if(strlen($results_version[$i]) > 2)
						$results_name[$i] .= " v" . $results_version[$i];

					if($results_result_format[$i] == "LINE_GRAPH")
						$t = new pts_LineGraph($results_name[$i], $results_attributes[$i], $results_scale[$i]);
					else if($results_result_format[$i] == "PASS_FAIL")
						$t = new pts_PassFailGraph($results_name[$i], $results_attributes[$i], $results_scale[$i]);
					else if($results_result_format[$i] == "MULTI_PASS_FAIL")
						$t = new pts_MultiPassFailGraph($results_name[$i], $results_attributes[$i], $results_scale[$i]);
					else
						$t = new pts_BarGraph($results_name[$i], $results_attributes[$i], $results_scale[$i]);

					$t->loadGraphIdentifiers($results_identifiers[$i]);
					$t->loadGraphValues($results_values[$i]);
					$t->loadGraphProportion($results_proportion[$i]);
					$t->loadGraphVersion(PTS_VERSION);
					$t->save_graph($save_to_dir . "/result-graphs/" . ($i + 1) . ".png");
					$t->renderGraph();
				}
			}
		}
		$bool = file_put_contents(SAVE_RESULTS_DIR . $save_to, $save_results);

		if(defined("TEST_RESULTS_IDENTIFIER") && pts_string_bool(pts_read_user_config(P_OPTION_LOG_VSYSDETAILS, "TRUE")))
		{
			// Save verbose system information here
			if(!is_dir($save_to_dir . "/system-details/"))
				mkdir($save_to_dir . "/system-details/");
			if(!is_dir($save_to_dir . "/system-details/" . TEST_RESULTS_IDENTIFIER))
				mkdir($save_to_dir . "/system-details/" . TEST_RESULTS_IDENTIFIER);

			// lspci
			$file = shell_exec("lspci 2>&1");

			if(strpos($file, "not found") == FALSE)
				@file_put_contents($save_to_dir . "/system-details/" . TEST_RESULTS_IDENTIFIER . "/lspci", $file);

			// sensors
			$file = shell_exec("sensors 2>&1");

			if(strpos($file, "not found") == FALSE)
				@file_put_contents($save_to_dir . "/system-details/" . TEST_RESULTS_IDENTIFIER . "/sensors", $file);

			// cpuinfo
			if(is_file("/proc/cpuinfo"))
			{
				$file = file_get_contents("/proc/cpuinfo");
				@file_put_contents($save_to_dir . "/system-details/" . TEST_RESULTS_IDENTIFIER . "/cpuinfo", $file);
			}
		}
	}

	return $bool;
}
function pts_process_register($process)
{
	// Register a process as active
	if(!is_dir(TEST_ENV_DIR))
		mkdir(TEST_ENV_DIR);
	if(!is_dir(TEST_ENV_DIR . ".processes"))
		mkdir(TEST_ENV_DIR . ".processes");

	return file_put_contents(TEST_ENV_DIR . ".processes/" . $process . ".p", getmypid());
}
function pts_process_remove($process)
{
	// Remove a process from being active, if present
	if(is_file(TEST_ENV_DIR . ".processes/" . $process . ".p"))
		return @unlink(TEST_ENV_DIR . ".processes/" . $process . ".p");
}
function pts_process_active($process)
{
	// Register a process as active
	$active = false;
	if(is_file(TEST_ENV_DIR . ".processes/" . $process . ".p") && !IS_SOLARIS)
	{
		$pid = trim(@file_get_contents(TEST_ENV_DIR . ".processes/" . $process . ".p"));
		$ps = trim(shell_exec("ps -p $pid 2>&1"));

		if(strpos($ps, "php") > 0)
			$active = true;
		else
			pts_process_remove($process);
	}
	return $active;
}
function display_web_browser($URL, $alt_text = NULL, $default_open = FALSE)
{
	// Launch the web browser
	if($alt_text == NULL)
		$text = "Do you want to view the results in your web browser";
	else
		$text = $alt_text;

	if(!$default_open)
		$view_results = pts_bool_question($text . " (y/N)?", false, "OPEN_BROWSER");
	else
		$view_results = pts_bool_question($text . " (Y/n)?", true, "OPEN_BROWSER");

	if($view_results)
		shell_exec("sh pts-core/scripts/launch-browser.sh \"" . $URL . "\" 2>&1");
}
function pts_env_variables()
{
	// The PTS environmental variables passed during the testing process, etc

	if(isset($GLOBALS["PTS_VAR_CACHE"]["ENV_VARIABLES"]))
	{
		$var_array = $GLOBALS["PTS_VAR_CACHE"]["ENV_VARIABLES"];
	}
	else
	{
		$var_array = array(
		"PTS_TYPE" => PTS_TYPE,
		"PTS_VERSION" => PTS_VERSION,
		"PTS_CODENAME" => PTS_CODENAME,
		"PTS_DIR" => PTS_DIR,
		"FONT_DIR" => FONT_DIR,
		"PHP_BIN" => PHP_BIN,
		"NUM_CPU_CORES" => cpu_core_count(),
		"NUM_CPU_JOBS" => cpu_job_count(),
		"SYS_MEMORY" => memory_mb_capacity(),
		"VIDEO_MEMORY" => graphics_memory_capacity(),
		"VIDEO_WIDTH" => current_screen_width(),
		"VIDEO_HEIGHT" => current_screen_height(),
		"VIDEO_MONITOR_COUNT" => graphics_monitor_count(),
		"VIDEO_MONITOR_LAYOUT" => graphics_monitor_layout(),
		"VIDEO_MONITOR_SIZES" => graphics_monitor_resolutions(),
		"OPERATING_SYSTEM" => pts_vendor_identifier(),
		"OS" => os_vendor(),
		"OS_VERSION" => os_version(),
		"OS_ARCH" => kernel_arch(),
		"OS_TYPE" => OPERATING_SYSTEM,
		"THIS_RUN_TIME" => THIS_RUN_TIME
		);

		$GLOBALS["PTS_VAR_CACHE"]["ENV_VARIABLES"] = $var_array;
	}

	return $var_array;
}
function pts_input_correct_results_path($path)
{
	// Correct an input path for an XML file
	if(strpos($path, "/") === FALSE)
	{
		$path = SAVE_RESULTS_DIR . $path;
	}
	if(strpos($path, ".xml") === FALSE)
	{
		$path = $path . ".xml";
	}
	return $path;
}
function pts_variables_export_string($vars = null)
{
	// Convert pts_env_variables() into shell export variable syntax
	$return_string = "";

	if($vars == null)
		$vars = pts_env_variables();
	else
		$vars = array_merge(pts_env_variables(), $vars);

	foreach($vars as $name => $var)
	{
		$return_string .= "export " . $name . "=" . $var . ";";
	}
	return $return_string . " ";
}
function pts_run_additional_vars($identifier)
{
	$extra_vars = array();

	$extra_vars["HOME"] = TEST_ENV_DIR . $identifier . "/";

	$ctp_extension_string = "";
	$extends = pts_test_extends_below($identifier);
	foreach($extends as $extended_test)
		if(is_dir(TEST_ENV_DIR . $extended_test . "/"))
			$ctp_extension_string .= TEST_ENV_DIR . $extended_test . ":";

	if(!empty($ctp_extension_string))
		$extra_vars["PATH"] = $ctp_extension_string . "\$PATH";

	if(count($extends) > 0)
		$extra_vars["TEST_EXTENDS"] = TEST_ENV_DIR . $extends[0];

	return $extra_vars;
}
function pts_exec($exec, $extra_vars = null)
{
	// Same as shell_exec() but with the PTS env variables added in
	return shell_exec(pts_variables_export_string($extra_vars) . $exec);
}
function pts_request_new_id()
{
	// Request a new ID for a counter
	global $PTS_GLOBAL_ID;
	$PTS_GLOBAL_ID++;

	return $PTS_GLOBAL_ID;
}
function pts_global_upload_result($result_file, $tags = "")
{
	// Upload a test result to the Phoronix Global database
	$test_results = file_get_contents($result_file);
	$test_results = str_replace(array("\n", "\t"), "", $test_results);
	$switch_tags = array("Benchmark>" => "B>", "Results>" => "R>", "Group>" => "G>", "Entry>" => "E>", "Identifier>" => "I>", "Value>" => "V>", "System>" => "S>", "Attributes>" => "A>");

	foreach($switch_tags as $f => $t)
		$test_results = str_replace($f, $t, $test_results);

	$ToUpload = base64_encode($test_results);
	$GlobalUser = pts_current_user();
	$GlobalKey = pts_read_user_config(P_OPTION_GLOBAL_UPLOADKEY, "");
	$tags = base64_encode($tags);
	$return_stream = "";

	$upload_data = array("result_xml" => $ToUpload, "global_user" => $GlobalUser, "global_key" => $GlobalKey, "tags" => $tags);
	$upload_data = http_build_query($upload_data);

	$http_parameters = array("http" => array("method" => "POST", "content" => $upload_data));

	$stream_context = stream_context_create($http_parameters);
	$opened_url = @fopen("http://www.phoronix-test-suite.com/global/user-upload.php", "rb", FALSE, $stream_context);
	$response = @stream_get_contents($opened_url);

	if($response !== false)
		$return_stream = $response;

	return $return_stream;
}
function pts_is_global_id($global_id)
{
	// Checks if a string is a valid Phoronix Global ID
	return pts_global_valid_id_string($global_id) && trim(@file_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=" . $global_id)) == "REMOTE_FILE";
}
function pts_global_download_xml($global_id)
{
	// Download a saved test result from Phoronix Global
	return @file_get_contents("http://www.phoronix-test-suite.com/global/pts-results-viewer.php?id=" . $global_id);
}
function pts_global_valid_id_string($global_id)
{
	// Basic checking to see if the string is possibly a Global ID
	$is_valid = true;

	if(count(explode("-", $global_id)) < 3) // Global IDs should have three (or more) dashes
		$is_valid = false;

	if(strlen($global_id) < 13) // Shorted Possible ID would be X-000-000-000
		$is_valid = false;

	return $is_valid;
}
function pts_trim_double($double, $accuracy = 2)
{
	// Set precision for a variable's points after the decimal spot
	$return = explode(".", $double);

	if(count($return) == 1)
		$return[1] = "00";

	if(count($return) == 2)
	{
		$strlen = strlen($return[1]);

		if($strlen > $accuracy)
			$return[1] = substr($return[1], 0, $accuracy);
		else if($strlen < $accuracy)
			for($i = $strlen; $i < $accuracy; $i++)
				$return[1] .= '0';

		$return = $return[0] . "." . $return[1];
	}
	else
		$return = $return[0];

	return $return;
}
function pts_bool_question($question, $default = true, $question_id = "UNKNOWN")
{
	// Prompt user for yes/no question
	if(defined("IS_BATCH_MODE") && IS_BATCH_MODE)
	{
		switch($question_id)
		{
			case "SAVE_RESULTS":
				$auto_answer = pts_read_user_config(P_OPTION_BATCH_SAVERESULTS, "TRUE");
				break;
			case "OPEN_BROWSER":
				$auto_answer = pts_read_user_config(P_OPTION_BATCH_LAUNCHBROWSER, "FALSE");
				break;
			case "UPLOAD_RESULTS":
				$auto_answer = pts_read_user_config(P_OPTION_BATCH_UPLOADRESULTS, "TRUE");
				break;
		}

		if(isset($auto_answer))
			$answer = $auto_answer == "TRUE" || $auto_answer == "1";
		else
			$answer = $default;
	}
	else
	{
		do
		{
			echo $question . " ";
			$input = trim(strtolower(fgets(STDIN)));
		}
		while($input != "y" && $input != "n" && $input != "");

		if($input == "y")
			$answer = true;
		else if($input == "n")
			$answer = false;
		else
			$answer = $default;
	}

	return $answer;
}
function pts_clean_information_string($str)
{
	// Clean a string containing hardware information of some common things to change/strip out
	$remove_phrases = array("corporation ", " technologies", ",", " technology", " incorporation", "version ", "computer ", "processor ", "genuine ", "unknown device ", "(r)", "(tm)", "inc. ", "inc ", "/pci/sse2/3dnow!", "/pci/sse2", "co. ltd", "co. ltd.", "northbridge only dual slot pci-e_gfx and ht3 k8 part", "northbridge only dual slot pci-e_gfx and ht1 k8 part", "host bridge", "dram controller");
	$str = str_ireplace($remove_phrases, " ", $str);

	$change_phrases = array("Memory Controller Hub" => "MCH", "Advanced Micro Devices" => "AMD", "MICRO-STAR INTERNATIONAL" => "MSI", "Silicon Integrated Systems" => "SiS", "Integrated Graphics Controller" => "IGP");

	foreach($change_phrases as $original_phrase => $new_phrase)
		$str = str_ireplace($original_phrase, $new_phrase, $str);

	$str = trim(preg_replace("/\s+/", " ", $str));

	return $str;
}
function pts_string_header($heading, $char = '=')
{
	// Return a string header
	$header_size = 36;

	foreach(explode("\n", $heading) as $line)
		if(($line_length = strlen($line)) > $header_size)
			$header_size = $line_length;

	$terminal_width = trim(shell_exec("tput cols 2>&1"));

	if($header_size > $terminal_width && $terminal_width > 1)
		$header_size = $terminal_width;

	return "\n" . str_repeat($char, $header_size) . "\n" . $heading . "\n" . str_repeat($char, $header_size) . "\n\n";
}
function pts_exit($string = "")
{
	// Have PTS exit abruptly
	define("PTS_EXIT", 1);
	echo $string;
	exit(0);
}
function pts_version_comparable($old, $new)
{
	// Checks if there's a major version difference between two strings, if so returns false. If the same or only a minor difference, returns true.
	$old = explode(".", $old);
	$new = explode(".", $new);
	$compare = true;

	if(count($old) >= 2 && count($new) >= 2)
		if($old[0] != $new[0] || $old[1] != $new[1])
			$compare = false;

	return $compare;	
}
function pts_shutdown()
{
	// Shutdown process for PTS
	define("PTS_END_TIME", time());

	// Re-run the config file generation to save the last run version
	pts_user_config_init();

	if(IS_DEBUG_MODE && defined("PTS_DEBUG_FILE"))
	{
		if(!is_dir(PTS_USER_DIR . "debug-messages/"))
			mkdir(PTS_USER_DIR . "debug-messages/");

		if(file_put_contents(PTS_USER_DIR . "debug-messages/" . PTS_DEBUG_FILE, $GLOBALS["DEBUG_CONTENTS"]))
			echo "\nDebug Message Saved To: " . PTS_USER_DIR . "debug-messages/" . PTS_DEBUG_FILE . "\n";
	}

	if(IS_SCTP_MODE)
		remote_sctp_test_files();

	// Remove process
	pts_process_remove("phoronix-test-suite");
}
function pts_string_bool($string)
{
	// Used for evaluating if the user inputted a string that evaluates to true
	$string = strtolower($string);
	return $string == "true" || $string == "1" || $string == "on";
}
function pts_is_valid_download_url($string, $basename = NULL)
{
	// Checks for valid download URL
	$is_valid = true;

	if(strpos($string, "://") == FALSE)
		$is_valid = FALSE;

	if(!empty($basename) && $basename != basename($string))
		$is_valid = FALSE;

	return $is_valid;
}
function pts_format_time_string($time, $format = "SECONDS")
{
	// Format an elapsed time string
	if($format == "MINUTES")
		$time *= 60;

	$formatted_time = array();

	if($time > 0)
	{
		$time_hours = floor($time / 3600);
		$time_minutes = floor(($time - ($time_hours * 3600)) / 60);
		$time_seconds = $time % 60;

		if($time_hours > 0)
		{
			$formatted_part = $time_hours . " Hour";

			if($time_hours > 1)
				$formatted_part .= "s";

			array_push($formatted_time, $formatted_part);
		}
		if($time_minutes > 0)
		{
			$formatted_part = $time_minutes . " Minute";

			if($time_minutes > 1)
				$formatted_part .= "s";

			array_push($formatted_time, $formatted_part);
		}
		if($time_seconds > 0)
		{
			$formatted_part = $time_seconds . " Second";

			if($time_seconds > 1)
				$formatted_part .= "s";

			array_push($formatted_time, $formatted_part);
		}
	}

	return implode(", ", $formatted_time);
}
function pts_evaluate_script_type($script)
{
	$script = explode("\n", trim($script));
	$script_eval = trim($script[0]);
	$script_type = false;

	if(strpos($script_eval, "<?php") !== FALSE)
		$script_type = "PHP";
	else if(strpos($script_eval, "#!/bin/sh") !== FALSE)
		$script_type = "SH";
	else if(strpos($script_eval, "<") !== FALSE && strpos($script_eval, ">") !== FALSE)
		$script_type = "XML";

	return $script_type;
}
function pts_set_environment_variable($name, $value)
{
	// Sets an environmental variable
	if(getenv($name) == FALSE)
		putenv($name . "=" . $value);
}
function pts_proximity_match($search, $match_to)
{
	// Proximity search in $search string for * against $match_to
	$search = explode("*", $search);
	$is_match = true;

	if(count($search) == 1)
		$is_match = false;

	for($i = 0; $i < count($search) && $is_match && !empty($search[$i]); $i++)
	{
		if(($match_point = strpos($match_to, $search[$i])) !== FALSE && ($i > 0 || $match_point == 0))
		{
			$match_to = substr($match_to, ($match_point + strlen($search[$i])));
		}
		else
			$is_match = false;
	}

	return $is_match;
}
function pts_debug_message($message)
{
	// Writes a PTS debug message
	if(IS_DEBUG_MODE)
	{
		if(strpos($message, "$") > 0)
			foreach(pts_env_variables() as $key => $value)
				$message = str_replace("$" . $key, $value, $message);

		echo "DEBUG: " . ($output = $message . "\n");

		if(defined("PTS_DEBUG_FILE"))
			$GLOBALS["DEBUG_CONTENTS"] = $output;
	}
}
function pts_user_message($message)
{
	if(!empty($message))
	{
		echo $message . "\n";

		if(!IS_BATCH_MODE)
		{
			echo "\nHit Any Key To Continue...\n";
			fgets(STDIN);
		}
	}
}

?>
