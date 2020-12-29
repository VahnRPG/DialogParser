<?php
require_once("reader.php");
require_once("parser.php");
require_once("tokens.php");

function find_files($dir, array $file_types, $files=array()) {
	if (is_dir($dir)) {
		$dh = opendir($dir);
		while (($file = readdir($dh)) !== false) {
			if ($file != "." && $file != "..") {
				if (is_dir($dir."/".$file)) {
					if (!preg_match('/\.svn/', $dir."/".$file)) {
						$files = find_files($dir."/".$file, $file_types, $files);
					}
				}
				else {
					foreach($file_types as $file_type) {
						if (preg_match('/\.'.$file_type.'$/', $file)) {
							$files[] = $dir."/".$file;
						}
					}
				}
			}
		}
		closedir($dh);
	}
	sort($files);
	
	return $files;
}

try {
	$files = find_files(getcwd()."/dialogs", array("reagd"));
	foreach($files as $file) {
		$reader = new Reader($file);
		$parser = new Parser($reader);
		$parser->parse();
		die;
	}
}
catch (\Exception $e) {
	echo "ERROR: ".$e->getMessage();
	exit(0);
}
?>