<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif(!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify that you put this file in the same place as SMF\'s index.php and SSI.php files.');

$oldSettings = array(
	'simplesef_enable',
	'simplesef_space',
	'simplesef_actions',
	'simplesef_user_actions',
	'simplesef_ignore_actions',
	'simplesef_advanced',
	'simplesef_aliases'
);

$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}settings
	WHERE variable IN ({array_string:settings})',
	array(
		'settings' => $oldSettings
	)
);

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
