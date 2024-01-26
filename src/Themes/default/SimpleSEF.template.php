<?php

function template_alias_settings(): void
{
	global $context, $txt;

	echo /** @lang text */ '
	<div>
		<form action="', $context['post_url'], '" method="post" accept-charset="', $context['character_set'], '">
			<div class="cat_bar">
				<h3 class="catbg">
					', $txt['simplesef_alias'], '
				</h3>
			</div>
			<div class="windowbg">
				<div class="content">
					<p>', $txt['simplesef_alias_detail'], '</p>';

	foreach ($context['simplesef_aliases'] as $original => $alias)
		echo /** @lang text */ '
					<div style="margin-top: 1ex;">
						<input type="text" name="original[]" value="', $original, '" size="20"> => <input type="text" name="alias[]" value="', $alias, '" size="20">
					</div>';

	echo /** @lang text */ '
					<noscript>
						<div style="margin-top: 1ex;"><input type="text" name="original[]" size="20" class="input_text"> => <input type="text" name="alias[]" size="20" class="input_text"></div>
					</noscript>
					<div id="moreAliases"></div>
					<div style="margin-top: 1ex; display: none;" id="moreAliases_link"><a href="#;" onclick="addNewAlias(); return false;">', $txt['simplesef_alias_clickadd'], /** @lang text */ '</a></div>
					<script>
						document.getElementById("moreAliases_link").style.display = "";
						function addNewAlias() {
							setOuterHTML(document.getElementById("moreAliases"), \'<div style="margin-top: 1ex;"><input type="text" name="original[]" size="20" class="input_text"> => <input type="text" name="alias[]" size="20" class="input_text"><\' + \'/div><div id="moreAliases"><\' + \'/div>\');
						}
					</script>
					<hr width="100%" size="1" class="hrcolor">
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">';

	if (isset($context['admin-dbsc_token_var']) && isset($context['admin-dbsc_token']))
		echo '
					<input type="hidden" name="', $context['admin-dbsc_token_var'], '" value="', $context['admin-dbsc_token'], '">';

	echo '
					<input type="submit" name="save" value="', $txt['save'], /** @lang text */ '" class="button">
				</div>
			</div>
		</form>
	</div>
	<br class="clear">';
}

function template_callback_simplesef_ignore(): void
{
	global $txt, $context, $modSettings;

	echo /** @lang text */ '
		<dt>
			<a id="simplesef_ignore"></a>
			<span>
				<label>', $txt['simplesef_ignore'], '</label><br>
				<span class="smalltext">', $txt['simplesef_ignore_desc'], /** @lang text */ '</span>
			</span>
		</dt>
		<dd>
			<select id="dummy_actions" multiple="multiple" size="9" style="min-width: 100px">';

	foreach ($context['simplesef_dummy_actions'] as $action)
		echo '
				<option value="', $action, '">', $action, '</option>';

	echo /** @lang text */ '
			</select>
			<span style="text-align: center; display: inline-block; position: relative; top: 32px;">
				<input type="button" id="simplesef_ignore_add" value="&raquo;" class="button"><br>
				<input type="button" id="simplesef_ignore_add_all" value="&raquo;&raquo;" class="button"><br>
				<input type="button" id="simplesef_ignore_remove_all" value="&laquo;&laquo;" class="button"><br>
				<input type="button" id="simplesef_ignore_remove" value="&laquo;" class="button"><br><br>
			</span>
			<select id="dummy_ignore" multiple="multiple" size="9" style="min-width: 100px">';

	foreach ($context['simplesef_dummy_ignore'] as $action)
		echo '
				<option value="', $action, '">', $action, '</option>';

	echo /** @lang text */ '
			</select>
			<input type="hidden" id="simplesef_ignore_actions" name="simplesef_ignore_actions" value="', empty($modSettings['simplesef_ignore_actions']) ? '' : $modSettings['simplesef_ignore_actions'], /** @lang text */ '">
		</dd>';
}
