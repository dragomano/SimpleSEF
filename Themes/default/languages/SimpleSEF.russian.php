<?php

$txt['simplesef'] = 'SimpleSEF';
$txt['simplesef_desc'] = 'В этом разделе можно изменить параметры SimpleSEF.<br><br>
<strong>Примечание: если после включения мода начали возникать ошибки 404 (страница не существует), это обычно связано с отсутствием файлов .htaccess или web.config. Либо на вашем сервере отключены или не установлены mod_rewrite или модуль Microsoft Url Rewrite (в зависимости от типа сервера). В этом случае использование мода будет невозможно.</strong> [<a href="#" onclick="showSimpleSEFHelp(); return false;">Справка</a>]
<div id="simplesef_help"><br>При использовании сервера Apache вам необходим файл .htaccess в корневой директории SMF, со следующим содержимым:' .
parse_bbc('[code]RewriteEngine On<br># Uncomment the following line if its not working right<br># RewriteBase /<br>RewriteCond %{REQUEST_FILENAME} !-f<br>RewriteCond %{REQUEST_FILENAME} !-d<br>RewriteRule ^(.*)$ index.php?q=$1 [L,QSA][/code]') . '
<br>
При использовании сервера IIS7 убедитесь в наличии файла web.config в корневой директории SMF, со следующим содержимым:' .
parse_bbc('[code]&lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&gt;<br>&lt;configuration&gt;<br>    &lt;system.webServer&gt;<br>        &lt;rewrite&gt;<br>            &lt;rules&gt;<br>                &lt;rule name=&quot;SimpleSEF&quot; stopProcessing=&quot;true&quot;&gt;<br>                    &lt;match url=&quot;^(.*)$&quot; ignoreCase=&quot;false&quot; /&gt;<br>                    &lt;conditions logicalGrouping=&quot;MatchAll&quot;&gt;<br>                        &lt;add input=&quot;{REQUEST_FILENAME}&quot; matchType=&quot;IsFile&quot; negate=&quot;true&quot; pattern=&quot;&quot; ignoreCase=&quot;false&quot; /&gt;<br>                        &lt;add input=&quot;{REQUEST_FILENAME}&quot; matchType=&quot;IsDirectory&quot; negate=&quot;true&quot; pattern=&quot;&quot; ignoreCase=&quot;false&quot; /&gt;<br>                    &lt;/conditions&gt;<br>                    &lt;action type=&quot;Rewrite&quot; url=&quot;index.php?q={R:1}&quot; appendQueryString=&quot;true&quot; /&gt;<br>                &lt;/rule&gt;<br>            &lt;/rules&gt;<br>        &lt;/rewrite&gt;<br>    &lt;/system.webServer&gt;<br>&lt;/configuration&gt;[/code]') . '
<br>
При использовании сервера Lighttpd v1.4.23 (или более ранней версии) проверьте, присутствует ли в файле конфигурации (обычно это файл /etc/lighttpd/lighttpd.conf) следующий текст:' .
parse_bbc('[code]$HTTP[&quot;host&quot;] =~ &quot;(www.)?example.com&quot; {<br>   url.rewrite-final += (<br>      # Allow all normal files<br>      &quot;^/forum/.*\.(js|ico|gif|jpg|png|swf|css|htm|php)(\?.*)?$&quot; =&gt; &quot;$0&quot;,<br>      # Rewrite everything else<br>      &quot;^/([^.?]*)$&quot; =&gt; &quot;/index.php?q=$1&quot;<br>   )<br>}[/code]') . '
<br>
Кроме того, можно добиться работы мода и на сервере Nginx, добавив в файл конфигурации следующий код:' .
parse_bbc('[code]if (!-e $request_filename) {<br>    rewrite ^/(.*)$ /index.php?q=$1 last;<br>}[/code]') . '
</div>
<script>
	document.getElementById("simplesef_help").style.display = "none";
	function showSimpleSEFHelp() {
		document.getElementById("simplesef_help").style.display = (document.getElementById("simplesef_help").style.display == "none") ? "" : "none";
	}
</script>';
$txt['simplesef_basic']            = 'Основные настройки';
$txt['simplesef_enable']           = 'Включить SimpleSEF';
$txt['simplesef_enable_desc']      = 'Требуется поддержка mod_rewrite (Apache) или web.config (IIS7).';
$txt['simplesef_space']            = 'Заменитель пробела';
$txt['simplesef_space_desc']       = 'Символ, используемый вместо пробела в адресах. Обычно _ (знак подчеркивания) или - (дефис).';
$txt['simplesef_action_title']     = 'Области (Actions)';
$txt['simplesef_action_desc']      = 'Здесь перечислены все области форума. Обычно вам не нужно ничего изменять в этом списке. Но если вы всё-таки решитесь на это, будьте готовы к тому, что что-нибудь перестанет работать. [<a href="#" onclick="return editAreas();">Правка</a>]';
$txt['simplesef_actions']          = 'Стандартные';
$txt['simplesef_user_actions']     = 'Пользовательские';
$txt['simplesef_404']              = 'Запрашиваемая страница не найдена. Пожалуйста, свяжитесь с администратором форума, если считаете, что попали на эту страницу по ошибке.';
$txt['simplesef_advanced']         = 'Дополнительно';
$txt['simplesef_advanced_desc']    = 'Разрешить использование псевдонимов, игнорирование некоторых областей и некоторые другие параметры.';
$txt['simplesef_alias']            = 'Псевдонимы';
$txt['simplesef_alias_desc']       = 'Использование псевдонимов помогает изменять имена любых областей (actions), без влияния на работу форума. Например, action страницы &laquo;Список пользователей&raquo; \'mlist\' можно без проблем поменять на \'users\'. У каждой области форума может быть только <strong>один</strong> псевдоним.';
$txt['simplesef_alias_clickadd']   = 'Добавить псевдоним';
$txt['simplesef_alias_detail']     = 'Укажите желаемую область слева, а справа — её псевдоним';
$txt['simplesef_ignore']           = 'Игнорируемые области';
$txt['simplesef_ignore_desc']      = 'Переместите области, которые вам не нужны, в список справа.';
