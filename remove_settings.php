<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif(!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify that you put this file in the same place as SMF\'s index.php and SSI.php files.');

// List settings here
$oldSettings = array(
	'simplesef_enable',
	'simplesef_space',
	'simplesef_actions',
	'simplesef_useractions',
	'simplesef_ignore_actions',
	'simplesef_advanced',
	'simplesef_aliases'
);

$sef_functions = array(
	'integrate_pre_include'    => '$sourcedir/SimpleSEF.php',
	'integrate_pre_load'       => 'SimpleSEF::convertQueryString#',
	'integrate_buffer'         => 'SimpleSEF::ob_simplesef#',
	'integrate_redirect'       => 'SimpleSEF::fixRedirectUrl#',
	'integrate_outgoing_email' => 'SimpleSEF::fixEmailOutput#',
	'integrate_exit'           => 'SimpleSEF::fixXMLOutput#',
	'integrate_admin_areas'    => 'SimpleSEF::adminAreas#',
	'integrate_admin_search'   => 'SimpleSEF::adminSearch#',
	'integrate_menu_buttons'   => 'SimpleSEF::menuButtons#',
	'integrate_actions'        => 'SimpleSEF::actionArray#'
);

if (!empty($smcFunc['db_query'])) {
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}settings
		WHERE variable IN ({array_string:settings})', array(
			'settings' => $oldSettings
		)
	);

	// Remove hooks (for 2.0)
	foreach ($sef_functions as $hook => $function)
		remove_integration_function($hook, $function);
} else
	db_query("DELETE FROM {$db_prefix}settings WHERE variable IN ('" . implode('\', \'', array_merge($oldSettings, array_keys($sef_functions))) . "')", __FILE__, __LINE__);

if (removeHtaccess() === false)
	log_error('Could not remove or edit .htaccess file upon uninstall of SimpleSEF', 'debug');

if (SMF == 'SSI') {
	fatal_error('<b>This isn\'t really an error, just a message telling you that the settings have been removed from the database!</b><br />');
	@unlink(__FILE__);
}

function removeHtaccess()
{
	global $boarddir;

	$htaccess_removal = '
RewriteEngine On
# Uncomment the following line if it\'s not working right
# RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?q=$1 [L,QSA]';

	if (file_exists($boarddir . '/.htaccess') && is_writable($boarddir . '/.htaccess')) {
		$current_htaccess = file_get_contents($boarddir . '/.htaccess');

		// Only change something if the mod hasn't been addressed yet.
		if (strpos($current_htaccess, 'RewriteRule ^(.*)$ index.php') !== false) {
			$new_htaccess = str_replace($htaccess_removal, '', $current_htaccess);
			if (strpos($new_htaccess, 'RewriteRule ^(.*)$ index.php') !== false)
				return false;
			if (($ht_handle = fopen($boarddir . '/.htaccess', 'wb'))) {
				fwrite($ht_handle, $new_htaccess);
				fclose($ht_handle);
				return true;
			} else
				return false;
		}
		else
			return true;
	} elseif (file_exists($boarddir . '/.htaccess'))
		return strpos(file_get_contents($boarddir . '/.htaccess'), 'RewriteRule ^(.*)$ index.php') === false;
	else
		return true;
}
