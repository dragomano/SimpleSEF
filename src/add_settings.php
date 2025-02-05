<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif(!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify that you put this file in the same place as SMF\'s index.php and SSI.php files.');

if (version_compare(PHP_VERSION, '8.0', '<'))
	die('This mod needs PHP 8.0 or greater. You will not be able to install/use this mod, contact your host and ask for a php upgrade.');

$newSettings = array(
	'simplesef_space'          => '-',
	'simplesef_ignore_actions' => 'dlattach,.xml,xmlhttp,viewsmfile,breezeajax,breezemood,breezecover',
	'simplesef_actions'        => 'activate,admin,announce,attachapprove,buddy,calendar,clock,coppa,credits,deletemsg,dlattach,editpoll,editpoll2,findmember,groups,help,helpadmin,jsmodify,jsoption,likes,loadeditorlocale,lock,lockvoting,login,login2,logintfa,logout,markasread,mergetopics,mlist,moderate,modifycat,movetopic,movetopic2,notify,notifyboard,notifytopic,pm,post,post2,printpage,profile,quotefast,quickmod,quickmod2,recent,reminder,removepoll,removetopic2,reporttm,requestmembers,restoretopic,search,search2,sendactivation,signup,signup2,smstats,suggest,spellcheck,splittopics,stats,sticky,theme,trackip,about:unknown,unread,unreadreplies,verificationcode,viewprofile,vote,viewquery,viewsmfile,who,.xml,xmlhttp',
	'simplesef_user_actions'   => 'profile'
);

updateSettings($newSettings);

if (addHtaccess() === false)
	log_error('Could not add or edit .htaccess file upon install of SimpleSEF', 'debug');

if (SMF == 'SSI') {
	fatal_error('<b>This isn\'t really an error, just a message telling you that the settings have been entered into the database!</b><br />');
	@unlink(__FILE__);
}

function addHtaccess()
{
	global $boarddir;

	$htaccess_addition = '
RewriteEngine On
# Uncomment the following line if it\'s not working right
# RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?q=$1 [L,QSA]';

	if (file_exists($boarddir . '/.htaccess') && is_writable($boarddir . '/.htaccess')) {
		$current_htaccess = file_get_contents($boarddir . '/.htaccess');

		// Only change something if the mod hasn't been addressed yet.
		if (strpos($current_htaccess, 'RewriteRule ^(.*)$ index.php') === false) {
			if (($ht_handle = @fopen(dirname(__FILE__) . '/.htaccess', 'ab'))) {
				fwrite($ht_handle, $htaccess_addition);
				fclose($ht_handle);
				return true;
			} else
				return false;
		} else
			return true;
	} elseif (file_exists($boarddir . '/.htaccess'))
		return strpos(file_get_contents($boarddir . '/.htaccess'), 'RewriteRule ^(.*)$ index.php') !== false;
	elseif (is_writable($boarddir)) {
		if (($ht_handle = fopen($boarddir . '/.htaccess', 'wb'))) {
			fwrite($ht_handle, trim($htaccess_addition));
			fclose($ht_handle);
			return true;
		} else
			return false;
	} else
		return false;
}
