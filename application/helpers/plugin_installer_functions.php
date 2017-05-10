<?php

function check_is_installed_plugin($table_prefix, $plugin_name) {
	
	$is_installed = false;
	
	$sql = "SELECT is_installed FROM ".$table_prefix."plugins WHERE name='$plugin_name'";
	$mysqli_res = mysqli_query($sql);
	if ($mysqli_res) {
		$rows = mysqli_fetch_assoc($mysqli_res);
		if (is_array($rows) && count($rows) > 0) {
			$is_installed = $rows['is_installed'] > 0;
		}
	}
	return $is_installed;
}

function check_is_active_plugin($table_prefix, $plugin_name) {

	$is_active = false;
	
	$sql = "SELECT is_activated FROM ".$table_prefix."plugins WHERE name='$plugin_name'";
	$mysqli_res = mysqli_query($sql);
	if ($mysqli_res) {
		$rows = mysqli_fetch_assoc($mysqli_res);
		if (is_array($rows) && count($rows) > 0) {
			$is_active = $rows['is_activated'] > 0;
		}
	}
	return $is_active;
}