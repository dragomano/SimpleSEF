<?php declare(strict_types=1);

/**
 * SimpleSEF.php
 *
 * @package SimpleSEF
 * @link https://github.com/dragomano/SimpleSEF
 * @author Matt Zuba (https://bitbucket.org/mattzuba/simplesef)
 * @contributors Suki (Jessica Gonzalez), Bugo
 * @copyright 2019-2024 Bugo
 * @license https://github.com/dragomano/SimpleSEF#MPL-2.0-1-ov-file MPL-2.0
 *
 * @version 2.4.7
 */

if (!defined('SMF'))
	die('No direct access...');

class SimpleSEF
{
	/**
	 * @var array All actions used in the forum (normally defined in index.php but may come from custom action mod too)
	 */
	protected array $actions = [];

	/**
	 * @var array All ignored actions used in the forum
	 */
	protected array $ignoreActions = ['admin', 'openidreturn', 'uploadAttach', '.xml', 'dlattach', 'viewsmfile', 'xmlhttp', 'sitemap', 'sitemap_xsl', 'tpshout', 'kpr-ajax'];

	/**
	 * @var array Actions that have aliases
	 */
	protected array $aliasActions = [];

	/**
	 * @var array Actions that may have a 'u' or 'user' parameter in the URL
	 */
	protected array $userActions = [];

	/**
	 * @var array Stores boards found in the output after a database query
	 */
	protected array $boardNames = [];

	/**
	 * @var array Stores topics found in the output after a database query
	 */
	protected array $topicNames = [];

	/**
	 * @var array Stores usernames found in the output after a database query
	 */
	protected array $userNames = [];

	/**
	 * @var array Tracks the available extensions
	 */
	protected array $extensions = [];

	/**
	 * @var string Space replacement
	 */
	protected string $spaceChar = '-';

	/**
	 * @var bool Properly track redirects
	 */
	protected static bool $redirect = false;

	public function hooks(): void
	{
		$this->convertQueryString();

		add_integration_function('integrate_actions', __CLASS__ . '::actions', false, __FILE__, true);
		add_integration_function('integrate_buffer', __CLASS__ . '::fixBuffer', false, __FILE__, true);
		add_integration_function('integrate_redirect', __CLASS__ . '::fixRedirectUrl', false, __FILE__, true);
		add_integration_function('integrate_outgoing_email', __CLASS__ . '::fixEmailOutput', false, __FILE__, true);
		add_integration_function('integrate_exit', __CLASS__ . '::fixXMLOutput', false, __FILE__, true);
		add_integration_function('integrate_admin_areas', __CLASS__ . '::adminAreas', false, __FILE__, true);
		add_integration_function('integrate_admin_search', __CLASS__ . '::adminSearch', false, __FILE__, true);
		add_integration_function('integrate_modify_basic_settings', __CLASS__ . '::modifyBasicSettings', false, __FILE__, true);
	}

	/**
	 * Initialize the mod
	 *
	 * @param bool $force Force the init to run again if already done
	 * @return void
	 */
	public function init(bool $force = false): void
	{
		global $modSettings;
		static $done = false;

		if ($done && !$force)
			return;

		$done = true;

		$this->loadBoardNames($force);
		$this->loadExtensions($force);

		$this->actions       = !empty($modSettings['simplesef_actions']) ? explode(',', $modSettings['simplesef_actions']) : [];
		$this->ignoreActions = array_merge($this->ignoreActions, !empty($modSettings['simplesef_ignore_actions']) ? explode(',', $modSettings['simplesef_ignore_actions']) : []);
		$this->aliasActions  = !empty($modSettings['simplesef_aliases']) ? safe_unserialize($modSettings['simplesef_aliases']) : [];
		$this->userActions   = !empty($modSettings['simplesef_user_actions']) ? explode(',', $modSettings['simplesef_user_actions']) : [];
		$this->spaceChar     = !empty($modSettings['simplesef_space']) ? $modSettings['simplesef_space'] : '-';

		// We need to fix our GET array too...
		parse_str(preg_replace('~&(\w+)(?=&|$)~', '&$1=', strtr($_SERVER['QUERY_STRING'], [';?' => '&', ';' => '&', '%00' => '', "\0" => ''])), $_GET);
	}

	/**
	 * Implements integrate_pre_load
	 * Converts the incoming query string 'q=' into a proper querystring and get
	 * variable array. q= comes from the .htaccess rewrite.
	 * Will have to figure out how to do some checking of other types of SEF mods
	 * and be able to rewrite those as well. However, we only rewrite our own urls
	 *
	 * @return void
	 */
	public function convertQueryString(): void
	{
		global $modSettings, $scripturl, $boardurl;

		if (empty($modSettings['simplesef_enable']) || (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $this->ignoreActions)))
			return;

		if (isset($_REQUEST['xml']))
			return;

		$this->init();
		$scripturl = $boardurl . '/index.php';

		// Make sure we know the URL of the current request.
		if (empty($_SERVER['REQUEST_URI']))
			$_SERVER['REQUEST_URL'] = $scripturl . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
		elseif (preg_match('~^([^/]+//[^/]+)~', $scripturl, $match) == 1)
			$_SERVER['REQUEST_URL'] = $match[1] . $_SERVER['REQUEST_URI'];
		else
			$_SERVER['REQUEST_URL'] = $_SERVER['REQUEST_URI'];

		if (!empty($modSettings['queryless_urls']))
			updateSettings(['queryless_urls' => '0']);

		if (SMF == 'SSI')
			return;

		// If the URL contains index.php but not our ignored actions, rewrite the URL
		if (str_contains($_SERVER['REQUEST_URL'], 'index.php') && !(isset($_GET['xml']) || (!empty($_GET['action']) && in_array($_GET['action'], $this->ignoreActions)))) {
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: ' . $this->getSefUrl($_SERVER['REQUEST_URL']));
			exit;
		}

		// Parse the url
		if (!empty($_GET['q'])) {
			$querystring = $this->route($_GET['q']);
			$_GET = $querystring + $_GET;
			unset($_GET['q']);
		}

		// Need to grab any extra query parts from the original url and tack it on here
		$_SERVER['QUERY_STRING'] = http_build_query($_GET, '', ';');
	}

	public function actions(array &$actions): void
	{
		$actions['simplesef-404'] = ['SimpleSEF.php', [$this, 'http404NotFound']];
	}

	public function http404NotFound(): void
	{
		loadLanguage('SimpleSEF');

		header('HTTP/1.0 404 Not Found');

		fatal_lang_error('simplesef_404', false, null, 404);
	}

	/**
	 * Implements integrate_buffer
	 * This is the core of the mod.  Rewrites the output buffer to create SEF
	 * urls.  It will only rewrite urls for the site at hand, not other urls
	 *
	 * @param string $buffer The output buffer after SMF has output the templates
	 * @return string Returns the altered buffer (or unaltered if the mod is disabled)
	 */
	public function fixBuffer(string $buffer): string
	{
		global $modSettings, $scripturl, $boardurl;

		if (empty($modSettings['simplesef_enable']) || (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $this->ignoreActions)))
			return $buffer;

		if (isset($_REQUEST['xml']))
			return $buffer;

		// Bump up our memory limit a bit
		if (@ini_get('memory_limit') < 128)
			@ini_set('memory_limit', '128M');

		// Grab the topics...
		$matches = [];
		preg_match_all('~\b' . preg_quote($scripturl) . '.*?topic=([0-9]+)~', $buffer, $matches);

		if (!empty($matches[1]))
			$this->loadTopicNames(array_unique($matches[1]));

		// We need to find urls that include a user id, so we can grab them all and fetch them ahead of time
		$matches = [];
		preg_match_all('~\b' . preg_quote($scripturl) . '.*?u=([0-9]+)~', $buffer, $matches);

		if (!empty($matches[1]))
			$this->loadUserNames(array_unique($matches[1]));

		// Grab all URLs and fix them
		$matches = [];
		preg_match_all('~\b(' . preg_quote($scripturl) . '[-a-zA-Z0-9+&@#/%?=\~_|!:,.;\[\]]*[-a-zA-Z0-9+&@#/%=\~_|\[\]]?)([^-a-zA-Z0-9+&@#/%=\~_|])~', $buffer, $matches);

		if (!empty($matches[0])) {
			$replacements = [];
			foreach (array_unique($matches[1]) as $i => $url) {
				$replacement = $this->getSefUrl($url);

				if ($url != $replacement)
					$replacements[$matches[0][$i]] = $replacement . $matches[2][$i];
			}

			$buffer = str_replace(array_keys($replacements), array_values($replacements), $buffer);
		}

		// We have to go fix up some javascript lying around in the templates
		$extra_replacements = [
			'/$d\',' => $this->spaceChar . '%1$d/\',', // Page index for MessageIndex
			'/rand,' => '/rand=', // Verification Image
			'%1.html$d\',' => '%1$d.html\',', // Page index on MessageIndex for topics
			$boardurl . '/topic/' => $scripturl . '?topic=', // Also for above
			'%1' . $this->spaceChar . '%1$d/\',' => '%1$d/\',', // Page index on Members listing
			'var smf_scripturl = "' . $boardurl . '/' => 'var smf_scripturl = "' . $scripturl
		];

		$buffer = str_replace(array_keys($extra_replacements), array_values($extra_replacements), $buffer);

		// Check to see if we need to update the actions lists
		$changeArray = [];
		$possibleChanges = ['actions', 'userActions'];
		foreach ($possibleChanges as $change)
			if (empty($modSettings['simplesef_' . strtolower($change)]) || (substr_count($modSettings['simplesef_' . strtolower($change)], ',') + 1) != count($this->$change))
				$changeArray['simplesef_' . strtolower($change)] = implode(',', $this->$change);

		if (!empty($changeArray)) {
			updateSettings($changeArray);
		}

		// I think we're done
		return $buffer;
	}

	/**
	 * Implements integrate_redirect
	 * When SMF calls redirectexit, we need to rewrite the URL its redirecting to
	 * Without this, the convertQueryString would catch it, but would cause an
	 * extra page load.  This helps reduce server load and streamlines redirects
	 *
	 * @param string $setLocation The original location (passed by reference)
	 * @return void
	 */
	public function fixRedirectUrl(string &$setLocation): void
	{
		global $scripturl, $modSettings;

		if (empty($modSettings['simplesef_enable']) || (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $this->ignoreActions)))
			return;

		static::$redirect = true;

		// Only do this if it's a URL for this board
		if (str_contains($setLocation, $scripturl))
			$setLocation = $this->getSefUrl($setLocation);
	}

	/**
	 * Implements integrate_exit
	 * When SMF outputs XML data, the buffer function is never called.  To
	 * circumvent this, we use the _exit hook which is called just before SMF
	 * exits.  If SMF didn't output a footer, it typically didn't run through
	 * our output buffer.  This catches the buffer and runs it through.
	 *
	 * @param bool $do_footer If we didn't do a footer and we're not wireless
	 * @return void
	 */
	public function fixXMLOutput(bool $do_footer): void
	{
		global $modSettings;

		if (empty($modSettings['simplesef_enable']) || (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $this->ignoreActions)))
			return;

		if (!$do_footer && !static::$redirect) {
			$temp = ob_get_contents();

			ob_end_clean();
			ob_start(!empty($modSettings['enableCompressedOutput']) ? 'ob_gzhandler' : '');
			ob_start([$this, 'fixBuffer']);

			echo $temp;
		}
	}

	/**
	 * Implements integrate_outgoing_mail
	 * Simply adjusts the subject and message of an email with proper urls
	 *
	 * @param string $subject The subject of the email
	 * @param string $message Body of the email
	 * @return boolean Always returns true to prevent SMF from erroring
	 */
	public function fixEmailOutput(string &$subject, string &$message): bool
	{
		global $modSettings;

		if (empty($modSettings['simplesef_enable']) || (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $this->ignoreActions)))
			return true;

		// We're just fixing the subject and message
		$subject = $this->fixBuffer($subject);
		$message = $this->fixBuffer($message);

		// We must return true, otherwise we fail!
		return true;
	}

	/**
	 * Implements integrate_admin_areas
	 * Adds SimpleSEF options to the admin panel
	 *
	 * @param array $admin_areas
	 * @return void
	 */
	public function adminAreas(array &$admin_areas): void
	{
		global $txt;

		loadLanguage('SimpleSEF');

		// We insert it after Features and Options
		$counter = array_search('featuresettings', array_keys($admin_areas['config']['areas'])) + 1;

		$admin_areas['config']['areas'] = array_merge(
			array_slice($admin_areas['config']['areas'], 0, $counter, true),
			[
				'simplesef' => [
					'label' => $txt['simplesef'],
					'function' => [$this, 'settings'],
					'icon' => 'packages',
					'subsections' => [
						'basic'    => [$txt['simplesef_basic']],
						'advanced' => [$txt['simplesef_advanced']],
						'alias'    => [$txt['simplesef_alias']]
					]
				]
			], array_slice($admin_areas['config']['areas'], $counter, count($admin_areas['config']['areas']), true)
		);
	}

	/**
	 * Easy access to mod settings via the quick search in the admin panel
	 *
	 * @param array $language_files
	 * @param array $include_files
	 * @param array $settings_search
	 * @return void
	 */
	public function adminSearch(array $language_files, array $include_files, array &$settings_search): void
	{
		$settings_search[] = [[$this, 'basicSettings'], 'area=simplesef;sa=basic'];
		$settings_search[] = [[$this, 'advancedSettings'], 'area=simplesef;sa=advanced'];
	}

	/**
	 * Directs the admin to the proper page of settings for SimpleSEF
	 *
	 * @return void
	 */
	public function settings(): void
	{
		global $sourcedir, $context, $txt;

		loadTemplate('SimpleSEF');

		require_once($sourcedir . '/ManageSettings.php');
		$context['page_title'] = $txt['simplesef'];

		$subActions = [
			'basic'    => 'basicSettings',
			'advanced' => 'advancedSettings',
			'alias'    => 'aliasSettings'
		];

		loadGeneralSettingParameters($subActions, 'basic');

		$codes = [
			parse_bbc('[code]RewriteEngine On<br># Uncomment the following line if its not working right<br># RewriteBase /<br>RewriteCond %{REQUEST_FILENAME} !-f<br>RewriteCond %{REQUEST_FILENAME} !-d<br>RewriteRule ^(.*)$ index.php?q=$1 [L,QSA][/code]'),
			parse_bbc('[code]&lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&gt;<br>&lt;configuration&gt;<br>    &lt;system.webServer&gt;<br>        &lt;rewrite&gt;<br>            &lt;rules&gt;<br>                &lt;rule name=&quot;SimpleSEF&quot; stopProcessing=&quot;true&quot;&gt;<br>                    &lt;match url=&quot;^(.*)$&quot; ignoreCase=&quot;false&quot; /&gt;<br>                    &lt;conditions logicalGrouping=&quot;MatchAll&quot;&gt;<br>                        &lt;add input=&quot;{REQUEST_FILENAME}&quot; matchType=&quot;IsFile&quot; negate=&quot;true&quot; pattern=&quot;&quot; ignoreCase=&quot;false&quot; /&gt;<br>                        &lt;add input=&quot;{REQUEST_FILENAME}&quot; matchType=&quot;IsDirectory&quot; negate=&quot;true&quot; pattern=&quot;&quot; ignoreCase=&quot;false&quot; /&gt;<br>                    &lt;/conditions&gt;<br>                    &lt;action type=&quot;Rewrite&quot; url=&quot;index.php?q={R:1}&quot; appendQueryString=&quot;true&quot; /&gt;<br>                &lt;/rule&gt;<br>            &lt;/rules&gt;<br>        &lt;/rewrite&gt;<br>    &lt;/system.webServer&gt;<br>&lt;/configuration&gt;[/code]'),
			parse_bbc('[code]$HTTP[&quot;host&quot;] =~ &quot;(www.)?example.com&quot; {<br>   url.rewrite-final += (<br>      # Allow all normal files<br>      &quot;^/forum/.*\.(js|ico|gif|jpg|png|swf|css|htm|php)(\?.*)?$&quot; =&gt; &quot;$0&quot;,<br>      # Rewrite everything else<br>      &quot;^/([^.?]*)$&quot; =&gt; &quot;/index.php?q=$1&quot;<br>   )<br>}[/code]'),
			parse_bbc('[code]if (!-e $request_filename) {<br>    rewrite ^/(.*)$ /index.php?q=$1 last;<br>}[/code]'),
			/** @lang text */
			'<script>
				document.getElementById("simplesef_help").style.display = "none";
				function showSimpleSEFHelp() {
					document.getElementById("simplesef_help").style.display = (document.getElementById("simplesef_help").style.display == "none") ? "" : "none";
				}
			</script>'
		];

		// Load up all the tabs...
		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $txt['simplesef'],
			'description' => sprintf($txt['simplesef_desc'], $codes[0], $codes[1], $codes[2], $codes[3], $codes[4]),
			'tabs' => [
				'basic'    => [],
				'advanced' => [],
				'alias'    => ['description' => $txt['simplesef_alias_desc']]
			]
		];

		$call = !empty($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $subActions[$_REQUEST['sa']] : 'basicSettings';
		$this->{$call}();
	}

	/**
	 * Modifies the basic settings of SimpleSEF.
	 *
	 * @param bool $return_config
	 * @return array|void
	 */
	public function basicSettings(bool $return_config = false)
	{
		global $sourcedir, $context, $txt, $scripturl, $modSettings, $boarddir;

		require_once($sourcedir . '/ManageServer.php');

		$context['page_title'] .= ' - ' . $txt['simplesef_basic'];
		$context['post_url'] = $scripturl . '?action=admin;area=simplesef;sa=basic;save';

		$config_vars = [
			['title', 'simplesef_basic'],
			['check', 'simplesef_enable', 'subtext' => $txt['simplesef_enable_desc']],
			['text', 'simplesef_space', 'size' => 6, 'subtext' => $txt['simplesef_space_desc']]
		];

		if ($return_config)
			return $config_vars;

		// Saving?
		if (isset($_GET['save'])) {
			checkSession();

			$save_vars = $config_vars;

			// We don't want to break boards, so we'll make sure some stuff exists before actually enabling
			if (!empty($_POST['simplesef_enable']) && empty($modSettings['simplesef_enable'])) {
				if (str_contains($_SERVER['SERVER_SOFTWARE'], 'IIS') && file_exists($boarddir . '/web.config'))
					$_POST['simplesef_enable'] = str_contains(implode('', file($boarddir . '/web.config')), '<action type="Rewrite" url="index.php?q={R:1}"') ? 1 : 0;
				elseif (! str_contains($_SERVER['SERVER_SOFTWARE'], 'IIS') && file_exists($boarddir . '/.htaccess'))
					$_POST['simplesef_enable'] = str_contains(implode('', file($boarddir . '/.htaccess')), 'RewriteRule ^(.*)$ index.php') ? 1 : 0;
				elseif (str_contains($_SERVER['SERVER_SOFTWARE'], 'lighttpd'))
					$_POST['simplesef_enable'] = 1;
				elseif (str_contains($_SERVER['SERVER_SOFTWARE'], 'nginx'))
					$_POST['simplesef_enable'] = 1;
				else
					$_POST['simplesef_enable'] = 0;
			}

			saveDBSettings($save_vars);
			redirectexit('action=admin;area=simplesef;sa=basic');
		}

		prepareDBSettingContext($config_vars);
	}

	/**
	 * Modifies the advanced settings for SimpleSEF.  Most setups won't need to
	 * touch this (except for maybe other languages)
	 *
	 * @param bool $return_config
	 * @return array|void
	 */
	public function advancedSettings(bool $return_config = false)
	{
		global $context, $txt, $modSettings, $scripturl;

		$context['page_title'] .= ' - ' . $txt['simplesef_advanced'];

		$config_vars = [
			['title', 'simplesef_advanced'],
			['callback', 'simplesef_ignore'],
			['title', 'title', 'label' => $txt['simplesef_action_title']],
			['desc', 'desc', 'label' => $txt['simplesef_action_desc']],
			['text', 'simplesef_actions', 'size' => 50, 'disabled' => 'disabled', 'preinput' => '<input type="hidden" name="simplesef_actions" value="' . ($modSettings['simplesef_actions'] ?? '') . '">'],
			['text', 'simplesef_user_actions', 'size' => 50, 'disabled' => 'disabled', 'preinput' => '<input type="hidden" name="simplesef_user_actions" value="' . ($modSettings['simplesef_user_actions'] ?? '') . '">']
		];

		if ($return_config)
			return $config_vars;

		// Prepare the actions and ignore list
		$context['simplesef_dummy_ignore'] = !empty($modSettings['simplesef_ignore_actions']) ? explode(',', $modSettings['simplesef_ignore_actions']) : [];
		$context['simplesef_dummy_actions'] = array_diff(explode(',', $modSettings['simplesef_actions']), $context['simplesef_dummy_ignore']);
		$context['post_url'] = $scripturl . '?action=admin;area=simplesef;sa=advanced;save';

		loadJavaScriptFile('SelectSwapper.js', ['minimize' => true]);
		$context['settings_post_javascript'] = '
			function editAreas() {
				document.getElementById("simplesef_actions").disabled = "";
				document.getElementById("setting_simplesef_actions").nextSibling.nextSibling.style.color = "";
				document.getElementById("simplesef_user_actions").disabled = "";
				document.getElementById("setting_simplesef_user_actions").nextSibling.nextSibling.style.color = "";
				return false;
			}
			var swapper = new SelectSwapper({
				sFromBoxId			: "dummy_actions",
				sToBoxId			: "dummy_ignore",
				sToBoxHiddenId		: "simplesef_ignore_actions",
				sAddButtonId		: "simplesef_ignore_add",
				sAddAllButtonId		: "simplesef_ignore_add_all",
				sRemoveButtonId		: "simplesef_ignore_remove",
				sRemoveAllButtonId	: "simplesef_ignore_remove_all"
			});';

		// Saving?
		if (isset($_GET['save'])) {
			checkSession();
			$save_vars = $config_vars;
			$save_vars[] = ['text', 'simplesef_ignore_actions'];
			saveDBSettings($save_vars);
			redirectexit('action=admin;area=simplesef;sa=advanced');
		}

		prepareDBSettingContext($config_vars);
	}

	/**
	 * Modifies the Action Aliasing settings
	 *
	 * @return void
	 */
	public function aliasSettings(): void
	{
		global $context, $modSettings, $txt, $scripturl;

		$context['sub_template'] = 'alias_settings';
		$context['simplesef_aliases'] = !empty($modSettings['simplesef_aliases']) ? safe_unserialize($modSettings['simplesef_aliases']) : [];
		$context['page_title'] .= ' - ' . $txt['simplesef_alias'];
		$context['post_url'] = $scripturl . '?action=admin;area=simplesef;sa=alias';

		if (isset($_POST['save'])) {
			checkSession();

			// Start with some fresh arrays
			$alias_original = [];
			$alias_new = [];

			// Clean up the passed in arrays
			if (isset($_POST['original'], $_POST['alias'])) {
				// Make sure we don't allow duplicate actions or aliases
				$_POST['original'] = array_unique(array_filter($_POST['original'], function($x) {return $x != '';}));
				$_POST['alias']    = array_unique(array_filter($_POST['alias'], function($x) {return $x != '';}));
				$alias_original    = array_intersect_key($_POST['original'], $_POST['alias']);
				$alias_new         = array_intersect_key($_POST['alias'], $_POST['original']);
			}

			$aliases = !empty($alias_original) ? array_combine($alias_original, $alias_new) : [];

			// One last check
			foreach ($aliases as $orig => $alias) {
				if ($orig == $alias)
					unset($aliases[$orig]);
			}

			$updates = [
				'simplesef_aliases' => safe_serialize($aliases)
			];

			updateSettings($updates);
			redirectexit('action=admin;area=simplesef;sa=alias');
		}
	}

	/**
	 * Remove queryless urls setting
	 *
	 * @param array $config_vars
	 * @return void
	 */
	public function modifyBasicSettings(array &$config_vars): void
	{
		global $modSettings;

		if (empty($modSettings['simplesef_enable']))
			return;

		foreach ($config_vars as $id => $config_var) {
			if (isset($config_var[1]) && $config_var[1] === 'queryless_urls') {
				unset($config_vars[$id]);
				break;
			}
		}
	}

	/**
	 * This is a helper function of sorts that actually creates the SEF urls.
	 * It compiles the different parts of a normal URL into a SEF style url
	 *
	 * @param string $url URL to SEFize
	 * @return string Either the original url if not enabled or ignored, or a new URL
	 */
	public function getSefUrl(string $url): string
	{
		global $modSettings, $sourcedir;

		if (empty($modSettings['simplesef_enable']))
			return $url;

		// Set our output strings to nothing.
		$sefstring = $sefstring2 = $sefstring3 = '';
		$query_parts = [];

		// Get the query string of the passed URL
		$url_parts = parse_url($url);
		$params = [];
		parse_str(!empty($url_parts['query']) ? preg_replace('~&(\w+)(?=&|$)~', '&$1=', strtr($url_parts['query'], ['&amp;' => '&', ';' => '&'])) : '', $params);

		if (!empty($params['action'])) {
			// If we're ignoring this action, just return the original URL
			if (in_array($params['action'], $this->ignoreActions)) {
				return $url;
			}

			if (!in_array($params['action'], $this->actions))
				$this->actions[] = $params['action'];

			$query_parts['action'] = $params['action'];
			unset($params['action']);

			if (!empty($params['u'])) {
				if (!in_array($query_parts['action'], $this->userActions))
					$this->userActions[] = $query_parts['action'];

				$query_parts['user'] = $this->getUserName($params['u']);
				unset($params['u'], $params['user']);
			}
		}

		if (!empty($query_parts['action']) && !empty($this->extensions[$query_parts['action']])) {
			require_once($sourcedir . '/SimpleSEF-Ext/' . $query_parts['action'] . '/' . $this->extensions[$query_parts['action']]);

			$class = ucwords($query_parts['action']);
			$extension = new $class();
			$sefstring2 = $extension->create($params);
		} else {
			if (empty($query_parts['action']) && !empty($params['board'])) {
				$query_parts['board'] = $this->getBoardName($params['board']);
				unset($params['board']);
			}

			if (empty($query_parts['action']) && !empty($params['topic'])) {
				$query_parts['topic'] = $this->getTopicName($params['topic']);
				unset($params['topic']);
			}

			foreach ($params as $key => $value) {
				if ($value == '') {
					$sefstring3 .= $key . '/';
				} else {
					$sefstring2 .= $key;
					if (is_array($value))
						$sefstring2 .= '[' . key($value) . '].' . $value[key($value)] . '/';
					else
						$sefstring2 .= '.' . urlencode(trim($value)) . '/';
				}
			}
		}

		// Fix the action if it's being aliased
		if (isset($query_parts['action']) && !empty($this->aliasActions[$query_parts['action']]))
			$query_parts['action'] = $this->aliasActions[$query_parts['action']];

		// Build the URL
		if (isset($query_parts['action']))
			$sefstring .= $query_parts['action'] . '/';

		if (isset($query_parts['user']))
			$sefstring .= $query_parts['user'] . '/';

		if (isset($query_parts['board']))
			$sefstring .= $query_parts['board'] . '/';

		if (isset($query_parts['topic']))
			$sefstring .= $query_parts['topic'] . '/';

		if (isset($sefstring2))
			$sefstring .= $sefstring2;

		if (isset($sefstring3))
			$sefstring .= $sefstring3;

		return str_replace('index.php' . (!empty($url_parts['query']) ? '?' . $url_parts['query'] : ''), $sefstring, $url);
	}

	/**
	 * Takes in a board name and tries to determine its id
	 *
	 * @param string $boardName
	 * @return string|false Will return false if it can't find an id or the id if found
	 */
	protected function getBoardId(string $boardName): string|false
	{
		if (($boardId = array_search($boardName, $this->boardNames)) !== false)
			return $boardId . '.0';

		if (($index = strrpos($boardName, $this->spaceChar)) === false)
			return false;

		$page = substr($boardName, $index + 1);
		if (is_numeric($page))
			$boardName = substr($boardName, 0, $index);
		else
			$page = '0';

		if (($boardId = array_search($boardName, $this->boardNames)) !== false)
			return $boardId . '.' . $page;
		else
			return false;
	}

	/**
	 * Generates a board name from the ID.  Checks the existing array and reloads
	 * it if it's not in there for some reason
	 *
	 * @param string $id
	 * @return string
	 */
	protected function getBoardName(string $id): string
	{
		if (empty($id))
			return '';

		if (stripos($id, '.') !== false) {
			$page = substr($id, stripos($id, '.') + 1);
			$id = substr($id, 0, stripos($id, '.'));
		}

		if (empty($this->boardNames[$id]))
			$this->loadBoardNames(true);

		$boardName = !empty($this->boardNames[$id]) ? $this->boardNames[$id] : 'board';

		if (isset($page) && ($page > 0))
			$boardName = $boardName . $this->spaceChar . $page;

		return $boardName;
	}

	/**
	 * Generates a topic name from its id.  This is typically called from
	 * self::getSefUrl which is called from self::fixBuffer which prepopulates topics.
	 * If the topic isn't prepopulated, it attempts to find it.
	 *
	 * @param string $id
	 * @return string Topic name with it's associated board name
	 */
	protected function getTopicName(string $id): string
	{
		$data = explode('.', $id);
		$value = $data[0];
		$start = $data[1] ?? 0;

		if (empty($this->topicNames[$value]))
			$this->loadTopicNames((int) $value);

		if (empty($this->topicNames[$value])) {
			$topicName = 'topic';
			$boardName = 'board';
		} else {
			$topicName = $this->topicNames[$value]['subject'];
			$boardName = $this->getBoardName($this->topicNames[$value]['board_id']);
		}

		return $boardName . '/' . $topicName . $this->spaceChar . $value . '.' . $start;
	}

	/**
	 * Generates a username from the ID.  See above comment block for
	 * pregeneration information
	 *
	 * @param int $id
	 * @return string User name
	 */
	protected function getUserName(int $id): string
	{
		if (empty($this->userNames[$id]))
			$this->loadUserNames($id);

		// And if it's still empty...
		if (empty($this->userNames[$id]))
			return 'user' . $this->spaceChar . $id;
		else
			return $this->userNames[$id] . $this->spaceChar . $id;
	}

	/**
	 * Takes the q= part of the query string passed in and tries to find out
	 * how to put the URL into terms SMF can understand.  If it can't, it forces
	 * the action to SimpleSEF's own 404 action and throws a nice error page.
	 *
	 * @param string $query Querystring to deal with
	 * @return array Returns an array suitable to be merged with $_GET
	 */
	protected function route(string $query): array
	{
		global $sourcedir;

		$url_parts = explode('/', trim($query, '/'));
		$querystring = [];

		$current_value = reset($url_parts);

		// Do we have an action?
		if ((in_array($current_value, $this->actions) || in_array($current_value, $this->aliasActions)) && !in_array($current_value, $this->ignoreActions)) {
			$querystring['action'] = array_shift($url_parts);

			// We may need to fix the action
			if (($reverse_alias = array_search($current_value, $this->aliasActions)) !== false)
				$querystring['action'] = $reverse_alias;

			$current_value = reset($url_parts);

			// User
			if (!empty($current_value) && in_array($querystring['action'], $this->userActions) && ($index = strrpos($current_value, $this->spaceChar)) !== false) {
				$user = substr(array_shift($url_parts), $index + 1);

				if (is_numeric($user))
					$querystring['u'] = intval($user);
				else
					$querystring['user'] = $user;

				$current_value = reset($url_parts);
			}

			if (!empty($this->extensions[$querystring['action']])) {
				require_once($sourcedir . '/SimpleSEF-Ext/' . $querystring['action'] . '/' . $this->extensions[$querystring['action']]);

				$class = ucwords($querystring['action']);
				$extension = new $class();
				$querystring += $extension->route($url_parts);

				// Empty it out, so it's not handled by this code
				$url_parts = [];
			}
		}

		if (!empty($url_parts)) {
			if (isset($url_parts[1]) && ! str_contains($url_parts[0], '.')) {
				$current_value = $url_parts[1];

				// Get the topic id
				$topic = $current_value;
				$topic = substr($topic, strrpos($topic, $this->spaceChar) + 1);
				$querystring['topic'] = $topic;
			} else {
				$current_value = array_pop($url_parts);

				// Check to see if the last one in the url array is a board
				if (preg_match('~^board_(\d+)$~', $current_value, $match))
					$board = $match[1];
				else
					$board = $this->getBoardId($current_value);

				if ($board !== false)
					$querystring['board'] = $board;
				else
					$url_parts[] = $current_value;
			}

			if (empty($querystring['action']) && empty($querystring['board']) && empty($querystring['topic']) && ! str_contains($url_parts[0], '.')) {
				$querystring['action'] = 'simplesef-404';
			}

			// Handle unknown variables
			$temp = [];
			foreach ($url_parts as $part) {
				if (str_contains($part, '.'))
					$part = substr_replace($part, '=', strpos($part, '.'), 1);

				parse_str($part, $temp);
				$querystring += $temp;
			}
		}

		//dd("'$query' => " . var_export($querystring, true));

		return $querystring;
	}

	/**
	 * Loads any extensions that other mod authors may have introduced
	 *
	 * @param bool $force
	 * @return void
	 */
	protected function loadExtensions(bool $force = false): void
	{
		global $sourcedir;

		if ($force || ($extensions = cache_get_data('simplesef_extensions', 36000)) === NULL) {
			$ext_dir = $sourcedir . '/SimpleSEF-Ext';
			$extensions = [];

			if (is_readable($ext_dir)) {
				$plugin_dirs = glob($ext_dir . '/*', GLOB_ONLYDIR);

				foreach ($plugin_dirs as $dir) {
					$dh = opendir($dir);

					while ($filename = readdir($dh)) {
						// Skip these
						if (in_array($filename, ['.', '..']) || preg_match('~ssef_([a-zA-Z_-]+)\.php~', $filename, $match) == 0)
							continue;

						$extensions[$match[1]] = $filename;
					}
				}
			}

			cache_put_data('simplesef_extensions', $extensions, 36000);
		}

		$this->extensions = $extensions;
	}

	/**
	 * Loads all board names from the forum into a variable and cache (if possible)
	 * This helps reduce the number of queries needed for SimpleSEF to run
	 *
	 * @param bool $force Forces a reload of board names
	 * @return void
	 */
	protected function loadBoardNames(bool $force = false): void
	{
		global $smcFunc;

		if ($force || ($boards = cache_get_data('simplesef_board_list', 36000)) === NULL) {
			$request = $smcFunc['db_query']('', /** @lang text */ '
				SELECT id_board, name
				FROM {db_prefix}boards',
				[]
			);

			$boards = [];
			while ($row = $smcFunc['db_fetch_assoc']($request)) {
				// A bit extra overhead to account for duplicate board names
				$temp_name = $this->getSlug($row['name']);
				$i = 0;

				while (!empty($boards[$temp_name . (!empty($i) ? $i + 1 : '')]))
					$i++;

				$boards[$temp_name . (!empty($i) ? $i + 1 : '')] = $row['id_board'];
			}

			$smcFunc['db_free_result']($request);

			cache_put_data('simplesef_board_list', $boards, 36000);
		}

		$this->boardNames = array_flip($boards);
	}

	/**
	 * Takes one or more topic id's, grabs their information from the database
	 * and stores it for later use.  Helps keep queries to a minimum.
	 *
	 * @param mixed $ids Can either be a single id or an array of ids
	 * @return void
	 */
	protected function loadTopicNames(mixed $ids): void
	{
		global $smcFunc;

		$ids = is_array($ids) ? $ids : [$ids];

		$request = $smcFunc['db_query']('', '
			SELECT t.id_topic, m.subject, t.id_board
			FROM {db_prefix}topics AS t
				INNER JOIN {db_prefix}messages AS m ON (m.id_msg = t.id_first_msg)
			WHERE t.id_topic IN ({array_int:topics})',
			[
				'topics' => $ids
			]
		);

		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$this->topicNames[$row['id_topic']] = [
				'subject'  => $this->getSlug($row['subject']),
				'board_id' => $row['id_board']
			];
		}

		$smcFunc['db_free_result']($request);
	}

	/**
	 * Takes one or more user ids and stores the usernames for those users for
	 * later user
	 *
	 * @param mixed $ids can be either a single id or an array of them
	 * @return void
	 */
	protected function loadUserNames(mixed $ids): void
	{
		global $smcFunc;

		$ids = is_array($ids) ? $ids : [$ids];

		$request = $smcFunc['db_query']('', '
			SELECT id_member, real_name
			FROM {db_prefix}members
			WHERE id_member IN ({array_int:members})',
			[
				'members' => $ids
			]
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$this->userNames[$row['id_member']] = $this->getSlug($row['real_name']);

		$smcFunc['db_free_result']($request);
	}

	/**
	 * Get a transliterated string
	 *
	 * @param string $string String to encode
	 * @return string Returns an encoded string
	 */
	protected function getSlug(string $string): string
	{
		global $sourcedir;

		if (empty($string))
			return '';

		require_once($sourcedir . '/SimpleSEF-Db/Transliterator.php');

		return \Behat\Transliterator\Transliterator::transliterate($string, $this->spaceChar);
	}
}
