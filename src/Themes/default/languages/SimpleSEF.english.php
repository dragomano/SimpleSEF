<?php

$txt['simplesef'] = 'SimpleSEF';
$txt['simplesef_desc'] = 'This section allows you to edit the options for SimpleSEF.<br><br>
<strong>Note: If you enable this and start receiving 404 errors on your board, it is likely because .htaccess or web.config was not created, or your host does not have the mod_rewrite or Microsoft Url Rewrite module installed on the web server and you will not be able to use this mod.</strong> [<a href="#" onclick="showSimpleSEFHelp(); return false;">Help</a>]
<div id="simplesef_help">If you have an Apache webserver, or one that uses .htaccess and has mod_rewrite functionality, you need a .htaccess file in your main SMF directory with the following: %s<br>
If you have a IIS7 webserver, you need a web.config file in your main SMF directory with the following: %s<br>
If you have Lighttpd v1.4.23 or less, you will need the following in your Lighttpd config file, normally at /etc/lighttpd/lighttpd.conf (thanks to <a href="https://www.simplemachines.org/community/index.php?action=profile;u=9547">Daniel15</a>): %s<br>
You can also make this work with Nginx with the following code added to your Nginx configuration file: %s
</div>%s';
$txt['simplesef_basic']            = 'Basic Options';
$txt['simplesef_enable']           = 'Enable SimpleSEF';
$txt['simplesef_enable_desc']      = 'Requires mod_rewrite support or Url Rewrite/web.config (IIS7) support.';
$txt['simplesef_space']            = 'Space replacement';
$txt['simplesef_space_desc']       = 'Character to be used instead of spaces in URLs.<br>Typically "_" (underscore) or "-" (hyphen).';
$txt['simplesef_action_title']     = 'Actions and User Actions';
$txt['simplesef_action_desc']      = 'These are all of the actions of the board. You normally do not need to edit this list. Infact, if you do edit these lists, it can cause parts of your board not to function temporarily. These are only provided to be edited if you are directed to do so. [<a href="#" onclick="return editAreas();">Edit</a>]';
$txt['simplesef_actions']          = 'Actions';
$txt['simplesef_user_actions']     = 'User Actions';
$txt['simplesef_404']              = 'The page you requested could not be found. Please contact the site administrator if you believe you have reached this page in error.';
$txt['simplesef_advanced']         = 'Advanced Options';
$txt['simplesef_advanced_desc']    = 'Enable action aliasing, action ignoring and other advanced options.';
$txt['simplesef_alias']            = 'Action Aliasing';
$txt['simplesef_alias_desc']       = 'Action Aliasing your actions allows you to change the name of an action without adversly affecting your board. For example, a Spanish language community may have a portal installed and will want to change the \'forum\' action to \'foro\'. This section makes it possible.  Each action can only have <strong>one</strong> alias and if you create an alias that is already an action, the action will take precedence, not the alias.';
$txt['simplesef_alias_clickadd']   = 'Add another alias';
$txt['simplesef_alias_detail']     = 'Enter the original action on the left, and what to change it to on the right';
$txt['simplesef_ignore']           = 'Ignore Actions';
$txt['simplesef_ignore_desc']      = 'Move actions you want to ignore to the box on the right';
