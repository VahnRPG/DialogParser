<?php
require_once("dialog.php");

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
	$dialogs = array();
	$files = find_files(getcwd()."/dialogs", array("reagd"));
	foreach($files as $file) {
		$dialog = new Dialog($file);
		$dialog->start(NULL, "init");
		$dialogs[$dialog->name] = $dialog;
	}
	
	foreach($dialogs as $dialog_name => $dialog) {
		$dialog->start("dylan", "start");
		do {
			if ($dialog->state == DialogStates::WAIT_CHOICE) {
				$dialog->choose(1);
			}
			$dialog->update();
		} while($dialog->state != DialogStates::NONE);
		echo "Done!\n";
		die;
	}
}
catch (\Exception $e) {
	echo "ERROR: ".$e->getMessage();
	exit(0);
}
?>