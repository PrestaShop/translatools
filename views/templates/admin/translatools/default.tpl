{* Like the good mathematician, the good programmer is lazy. *}
{function yesno label="Yes or No?" input_name="yes_or_no" value_on="1" value_off="0" label_on="Yes" label_off="No" value="1"}
	{assign var=id_on value=$input_name|replace:'[':'_'|replace:']':'_'|cat:'_on'}
	{assign var=id_off value=$input_name|replace:'[':'_'|replace:']':'_'|cat:'_off'}

    <div class="form-group">
		<label for="{$id_on}" class="control-label col-lg-3">{$label|escape:'htmlall':'UTF-8'}</label>
		<div class="col-lg-2">
			<span class="switch prestashop-switch fixed-width-lg">
				<input name="{$input_name}" type="radio" id="{$id_on}" value="{$value_on}"{if $value==$value_on} checked{/if}>
				<label for="{$id_on}">
					{l s='Yes' mod='translatools'|escape:'htmlall':'UTF-8'}
				</label>
				<input name="{$input_name}" type="radio" id="{$id_off}" value="{$value_off}"{if $value==$value_off} checked{/if}>
				<label for="{$id_off}">
					{l s='No' mod='translatools'|escape:'htmlall':'UTF-8'}
				</label>
				<a class="slide-button btn"></a>
			</span>
		</div>
	</div>
{/function}

<style>
	span.confirm>button
	{
		margin-right: 5px;
	}
	span.success
	{
		color: green;
		font-weight: bold;
	}
	span.error
	{
		color: red;
		font-weight: bold;
	}
	span.neutral
	{
		color: #333;
	}
</style>

<div class="alert alert-warning">
	<p><strong>{l s='Warning:' mod='translatools'}</strong> {l s='This module should never be used on a production shop!' mod='translatools'}</p>
	{if !$devServer}
		<p>We detected that your shop is not in dev mode, so as a precaution Live Translation was not enabled.</p>
		<form id="force-live-translation" action="" method="POST">
			<input type="hidden" name="force_live_translation" value="1">
			<p><button class="btn btn-warning" onclick="javascript:return confirm('Really activate Live Translation?? You should not do this on a production shop.');">Click here</button> to enable Live Translation anyway at your own risk!</p>
		</form>
	{else}
		<span id="live-translation-forced" style="display:none"></span>
	{/if}
</div>

{if $non_writable_directories|count > 0}
	<div class="alert alert-warning">
		<p><strong>{l s='Warning:' mod='translatools'}</strong> {l s='Some folders in which this module may need to create files cannot be written to. This will likely prevent you from using the module to its full potential.' mod='translatools'}</p>
		<p>{l s='Please try to adjust file permissions so that your web server may be able to create the following directories (if they do not exist) and write into them:' mod='translatools'}</p>
		<ul>
			{foreach from=$non_writable_directories item=dir}
				<li>{$dir}</li>
			{/foreach}
		</ul>
	</div>
{/if}

{if $shop_not_up_to_date}
	<div class="alert alert-warning">
		<p><strong>{l s='Warning:' mod='translatools'}</strong> {$shop_not_up_to_date}
	</div>
{/if}

<span style="display:none" id="translatability" data-translatability='{$coverage[null].percent_translated|intval}'></span>

<span style="display:none" id="check_pack_version" data-pack-version="{$pack_version}"></span>

<div class="alert alert-info">
	<p>{l s="Translatability level:" mod='translatools'} <strong>{$coverage[null].percent_translated|intval}%</strong>.</p>
	<p>{l s="Your PrestaShop version: %s" mod='translatools' sprintf=[$pack_version]}</p>
	<p>{l s="The translatability level is a measure of how much of your shop's messages can be translated using the official translation packs." mod='translatools'}</p>
	<p>{l s="A translatability of 100%% doesn't mean that your shop will be fully translated if you install translation packs." mod='translatools'} {l s="It only means that if you install a 100%% translation pack it will cover 100%% of what can be covered by any official PrestaShop translation pack." mod='translatools'}</p>
	<p>{l s='Translatability may be lower than 100%% for different reasons:' mod='translatools'}</p>
	<ul>
		<li>{l s="The 'Live Translation' language was not installed on your shop or something went wrong while extracting the strings from your installation. You will usually see 0%% coverage when this happens." mod='translatools'}</li>
		<li>{l s="You have unsupported modules and / or themes installed." mod='translatools'}</li>
		<li>{l s="PrestaShop has evolved since you last downloaded tranlations from Crowdin or PrestaShop hasn't yet updated Crowdin translations to reflect the new state of the software this will usually happen if you are using an unstable version of PrestaShop." mod='translatools'}</li>
	</ul>
	{if $coverage[null].percent_translated < 100}
		<p>{l s="The following translation files were found on your shop and do not have full translatability:" mod='translatools'}</p>
		<ul>
			{foreach from=$coverage item=details key=file}
				{if $file and $details.percent_translated < 100}
					<li>{$file} ({$details.percent_translated|intval})%</li>
				{/if}
			{/foreach}
		</ul>
	{else}
		<p><strong>{l s='Yay!' mod='translatools'}</strong> {l s='Your shop is 100% translatable :) You should be able to use Live Translation to its full potential.' mod='translatools'}</p>
	{/if}
</div>

<div class="panel">
	<h3>{l s='Export translations' mod='translatools'}</h3>

	<form class="form-horizontal" action="{$link->getAdminLink('AdminTranslatools')}" method="POST">

		{yesno label={l s='Export Front-Office Strings' mod='translatools'} input_name="section[frontOffice]"}

		{yesno label={l s='Export Back-Office Strings' mod='translatools'} input_name="section[backOffice]"}

		{yesno label={l s='Export Module Strings' mod='translatools'} input_name="section[modules]"}

		<div class="form-group">
			<label class="control-label col-lg-3" for="filter_modules">{l s='Which modules to parse?' mod='translatools'}</label>
			<div class="col-lg-6">
                <select name="filter_modules" id="filter_modules">
                    <option value="native" selected>{l s='Native' mod='translatools'}</option>
					<option value="all">{l s='All' mod='translatools'}</option>
				</select>
			</div>
		</div>

		{if $modules_not_found_warning}
			<div class="row">
				<div class="col-lg-3"></div>
				<div class="col-lg-6">
					<div class="alert alert-warning">
                        <strong>{l s='Warning:' mod='translatools'} </strong>{$modules_not_found_warning}
					</div>
				</div>
			</div>
		{else}
			<span style='display:none' id='modules-not-missing'></span>
		{/if}

		<div class="form-group">
			<label class="control-label col-lg-3" for="overriden_modules">{l s='Which types of modules to parse?' mod='translatools'}</label>
			<div class="col-lg-6">
				<select name="overriden_modules" id="overriden_modules">
					<option value="both">{l s='Core and Overriden' mod='translatools'}</option>
					<option value="core">{l s='Core Only' mod='translatools'}</option>
					<option value="overriden">{l s='Overriden Only' mod='translatools'}</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-lg-3" for="modules_storage">{l s='Where to store their translations?' mod='translatools'}</label>
			<div class="col-lg-6">
				<select name="modules_storage" id="modules_storage">
					<option value="core">{l s='All in core' mod='translatools'}</option>
					<option value="theme">{l s='Each in its place' mod='translatools'}</option>
				</select>
			</div>
		</div>

		{yesno label={l s='Export Errors Strings' mod='translatools'} input_name="section[errors]"}
		{yesno label={l s='Export PDFs Strings' mod='translatools'} input_name="section[pdfs]"}
		{yesno label={l s='Export Tabs Strings' mod='translatools'} input_name="section[tabs]"}
		{yesno label={l s='Export Mail Subject Strings' mod='translatools'} input_name="section[mailSubjects]"}
		{yesno label={l s='Export Mail Content Strings' mod='translatools'} input_name="section[mailContent]"}
		{yesno label={l s='Export Generated Emails' mod='translatools'} input_name="section[generatedEmails]"}
		{yesno label={l s='Export Installer Strings' mod='translatools'} input_name="section[installer]"}
		{yesno label={l s='Export Fields Strings' mod='translatools'} input_name="section[fields]"}


		<div class="form-group">
			<label class="control-label col-lg-3" for="theme">{l s='Theme' mod='translatools'}</label>
			<div class="col-lg-6">
				<select name="theme" id="theme">
					{foreach from=$themes item=theme}
						<option value="{$theme}" {if $theme === $current_theme}selected{/if}>{$theme|escape:'htmlall':'UTF-8'}</option>
					{/foreach}
				</select>
			</div>
		</div>
		<div class="form-group">
			<label for="language" class="control-label col-lg-3">{l s='Export language' mod='translatools'}</label>
			<div class="col-lg-6">
					<select name="language" id="language">
					<option value="-">{l s='As in code (should be English)' mod='translatools'}</option>
					{foreach from=$languages item=language key=code}
						<option value="{$code}">{$language|escape:'htmlall':'UTF-8'}</option>
					{/foreach}
				</select>
			</div>
		</div>
		<div class="form-group">
			<div class="col-lg-3"></div>
			<div class="col-lg-9">
				<button  class="btn btn-primary" name="action" value="exportTranslations">{l s='Export Now' mod='translatools'}</button>
				<button  class="btn btn-primary" name="action" value="viewStats">{l s='View Stats' mod='translatools'}</button>
			</div>
		</div>
	</form>
</div>

<div class="panel">
	<h3>{l s='Crowdin API' mod='translatools'}</h3>
	<div class="alert alert-info">
		<p>{l s='Only the project idenfifier is required here (to use Just In Place Translation). The default value should be fine.' mod='translatools'}</p>
		<p>{l s='If you are not an administrator of the PrestaShop Crowdin project you should not need to worry about the API Key and leave it blank. It is used to unlock advanced features that are not needed by translators.' mod='translatools'}</p>
	</div>
	<form action="{$link->getAdminLink('AdminTranslatools')}&amp;action=default" method="POST" class="form-horizontal">
		<input type="hidden" name="update_api_settings" value="1">
		<div class="form-group">
			<label class="control-label col-lg-3" for="CROWDIN_PROJECT_IDENTIFIER">{l s='Project Identifier' mod='translatools'}</label>
			<div class="col-lg-9">
				<div class="row">
					<div class="col-lg-8"><input class="form-control" value="{$CROWDIN_PROJECT_IDENTIFIER}" id="CROWDIN_PROJECT_IDENTIFIER" name="CROWDIN_PROJECT_IDENTIFIER" type="text" placeholder="prestashop-test-api"></div>
				</div>

			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-lg-3" for="CROWDIN_PROJECT_API_KEY">{l s='Project API Key' mod='translatools'}</label>
			<div class="col-lg-9">
				<div class="row">
					<div class="col-lg-8"><input class="form-control" value="{$CROWDIN_PROJECT_API_KEY}" id="CROWDIN_PROJECT_API_KEY" name="CROWDIN_PROJECT_API_KEY" type="text" placeholder="a2f1g5e8a6b7d4g5e2c1234a5e6f8c33"></div>
				</div>
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-lg-3" for="CROWDIN_FORCED_VERSION">{l s='Force PS Version' mod='translatools'}</label>
			<div class="col-lg-9">
				<div class="row">
					<div class="col-lg-8"><input class="form-control" value="{$CROWDIN_FORCED_VERSION}" id="CROWDIN_FORCED_VERSION" name="CROWDIN_FORCED_VERSION" type="text" placeholder="1.6-dev"></div>
				</div>
			</div>
		</div>
		<div class="form-group">
			<div class="col-lg-3"></div>
			<div class="col-lg-8">
				<button id="save-settings" class="btn btn-default">{l s='Save' mod='translatools'}</button>
			</div>
		</div>
	</form>
</div>

<div class="panel">
	<h3>{l s='Crowdin Integration' mod='translatools'}</h3>

	<form class="form-horizontal">
		{yesno input_name=jipt_bo label={l s='Enable Live Translation in Back-Office' mod='translatools'} value=$jipt_bo}
		{yesno input_name=jipt_fo label={l s='Enable Live Translation in Front-Office' mod='translatools'} value=$jipt_fo}
	</form>

	<div class="row">
		<div class="col-lg-3"></div>
		<div class="col-lg-6">
			<p class="help-block">{l s='JIPT Virtual Language is set to' mod='translatools'} <strong>{$jipt_language}</strong>.</p>
			{if !isset($languages[$jipt_language])}
				<form action="{$link->getAdminLink('AdminTranslatools')}&amp;action=createVirtualLanguage" method="POST"><p class="alert alert-info">{l s='The virtual language was not created on this shop though, you need to' mod='translatools'} <button type="submit" class="btn btn-primary">{l s='Create It' mod='translatools'}</button> {l s='before you can use Crowdin Live Translation.' mod='translatools'}</p></form>
			{/if}
		</div>
	</div>

	{if isset($CROWDIN_PROJECT_API_KEY) and ($CROWDIN_PROJECT_API_KEY != '')}
		<form class="form-horizontal">
			<div class="form-group">
				<label for="export" class="control-label col-lg-3">{l s='Export Sources to Crowdin' mod='translatools'}</label>
				<div class="col-lg-6">
					<div class="row">
						<div class="col-lg-4">
							<span class="confirm">
								<button type="button" data-confirm="Are you sure?" data-cancel="No" onclick="javascript:exportSourcesToCrowdin();" id="export" class="btn btn-warning">{l s='Export!' mod='translatools'}</button>
							</span>
						</div>
						<div class="col-lg-8">
							<p class="form-control-static" id="export-to-crowdin-feedback"></p>
						</div>
					</div>
				</div>
			</div>
		</form>

		<form class="form-horizontal">
			<div class="form-group">
				<label for="export-translations-to-crowdin" class="control-label col-lg-3">{l s='Export Translations to Crowdin' mod='translatools'}</label>
				<div class="col-lg-6">
					<div class="alert alert-warning">
                        <p>{l s="You need to export the 'As in code' language before pushing to Crowdin." mod='translatools'}</p>
						<p>{l s="Exporting the 'As in code' language will download an archive containing all the strings selected for export. This is so that you can review what is exported, but, if you are satisfied with it, you don't need to do anything more with this file." mod='translatools'}</p>
					</div>
					<div class="row">
						<div class="col-lg-2">
							<select name="language" id="export-translations-language">
								<option value="*">{l s='All languages' mod='translatools'}</option>
								{foreach from=$languages item=language key=code}
									<option value="{$code}">{$language|escape:'htmlall':'UTF-8'}</option>
								{/foreach}
							</select>
						</div>
						<div class="col-lg-4">
							<span class="confirm">
								<button type="button" data-confirm="Are you sure?" data-cancel="No" onclick="javascript:exportTranslationsToCrowdin();" id="export-translations-to-crowdin" class="btn btn-warning">{l s='Export!' mod='translatools'}</button>
							</span>
						</div>
						<div class="col-lg-6 feedback">
							<p class="form-control-static" id="export-translations-to-crowdin-feedback"></p>
						</div>
					</div>
				</div>
			</div>
		</form>

		<form class="form-horizontal">
			<div class="form-group">
				<label for="export" class="control-label col-lg-3">{l s='Regenerate Crowdin Translations' mod='translatools'}</label>
				<div class="col-lg-6">
					<div class="row">
						<div class="col-lg-4">
							<span class="confirm">
								<button type="button" data-confirm="Sure?" data-cancel="Well, no thanks." onclick="javascript:regenerateCrowdinTranslations();" class="btn btn-default">{l s='Regenerate!' mod='translatools'}</button>
							</span>
						</div>
						<div class="col-lg-8 feedback">
							<p class="form-control-static" id="regenerate-translations-feedback"></p>
						</div>
					</div>
				</div>
			</div>
		</form>
	{/if}
	<form class="form-horizontal">
		<div class="form-group">
			<label for="export" class="control-label col-lg-3">{l s='Install translations from Crowdin' mod='translatools'}</label>
			<div class="col-lg-6">
				<div class="alert alert-info">
					<p>{l s='This will install the latest translation files from Crowdin onto your Shop.' mod='translatools'}</p>
					<p>{l s='You should run this action regularly during pre release times if you want to be able to use Live Translation on all the strings in PrestaShop.' mod='translatools'}</p>
				</div>
				<div class="alert alert-warning">
					<p><strong>{l s="Warning:" mod='translatools'}</strong> {l s="Downloading translations from Crowdin will overwrite all or most of your own translations, in all languages. Only proceed if you really know what you're doing!" mod='translatools'}</p>
					<p>{l s="This action will install translation files for all supported languages, even if they are not installed on your shop. So this will create a lot of files!" mod='translatools'}</p>
				</div>
				<div class="row">
					<div class="col-lg-{if isset($CROWDIN_PROJECT_API_KEY)}2{else}4{/if}">
						<span class="confirm">
							<button data-confirm="Sure?" data-cancel="Nope." type="button" onclick="javascript:downloadTranslationsFromCrowdin();" class="btn btn-primary">{l s='Install!' mod='translatools'}</button>
						</span>
					</div>
					<div class="col-lg-2">
						<select id="install_language">
							<option value="all">All languages</option>
							{foreach from=$languages item=language key=code}
								<option value="{$code}">{$language}</option>
							{/foreach}
						</select>
					</div>
					<div class="col-lg-8 feedback">
						<p class="form-control-static" id="download-from-crowdin-feedback"></p>
					</div>
				</div>
			</div>
		</div>
	</form>
	<form method="POST" id="build-and-download-packs" action="{$link->getAdminLink('AdminTranslatools')}&amp;action=build" class="form-horizontal">
		<div class="form-group">
			<label for="build_packs" class="col-lg-3 control-label">
				{l s='Build Language Packs' mod='translatools'}
			</label>
			<div class="col-lg-2"><input placeholder="specified single code, else all" class="form-control" name="build_code"></div>
			<div class="col-lg-7">
				<button type="submit" class="btn btn-default" id="build_packs">{l s='Build & Download' mod='translatools'}</button>
			</div>
		</div>
	</form>
</div>

<div class="panel">
	<h3>{l s='Tasks' mod='translatools'}</h3>
	<form class="form-horizontal" method="POST" action="{$link->getAdminLink('AdminTranslatools')}&amp;action=copyTabs">
		<div class="form-group">
			<label for="copy_tabs" class="control-label col-lg-3">{l s='Copy Tabs to Installer XML files' mod='translatools'}</label>
			<div class="col-lg-9">
				<button id="copy_tabs" type="submit" class="btn btn-primary">{l s='Copy' mod='translatools'}</button>
			</div>
		</div>
	</form>
</div>

<div class="panel">
	<h3>{l s='Translation Linting' mod='translatools'}</h3>
	<form action="{$link->getAdminLink('AdminTranslatools')}&amp;action=checkCoherence" method="POST" class="form-horizontal">
		<div class="form-group">
			<label for="check-coherence" class="control-label col-lg-3">{l s='Check Overriding Coherence' mod='translatools'}</label>
			<div class="col-lg-6">
				<button type="submit" class="btn btn-primary">{l s='Check!' mod='translatools'}</button>
			</div>
		</div>
	</form>

	<form action="{$link->getAdminLink('AdminTranslatools')}&amp;action=checkQuality" method="POST" class="form-horizontal">
		<div class="form-group">
			<label for="check-quality" class="control-label col-lg-3">{l s='Check English Quality' mod='translatools'}</label>
			<div class="col-lg-6">
				<button type="submit" class="btn btn-primary">{l s='Check!' mod='translatools'}</button>
			</div>
		</div>
	</form>

	<form method="POST" action="{$link->getAdminLink('AdminTranslatools')}&amp;action=checkLUse" class="form-horizontal">
		<div class="form-group">
			<label class="control-label col-lg-3" for="check_l">{l s='Check use of translation functions' mod='translatools'}</label>
			<div class="col-lg-6">
				<select name="theme" id="theme_lint">
					{foreach from=$themes item=theme}
						<option value="{$theme}" {if $theme === $current_theme}selected{/if}>{$theme}</option>
					{/foreach}
				</select>
			</div>
			<div class="col-lg-3">
				<button class="btn" id="check_l">{l s='Check!' mod='translatools'}</button>
			</div>
		</div>
	</form>
</div>

<div class="panel">
	<h3>{l s='Purge Translations' mod='translatools'}</h3>
	<div class='alert alert-warning'>
		{l s='This will delete all translation files on your shop (except e-mails).' mod='translatools'}
	</div>
	<form action="{$link->getAdminLink('AdminTranslatools')}&amp;action=purgeTranslations" method="POST">
		<button onclick="javascript:return confirm('Really purge all translations??');" class="btn btn-warning">{l s='Purge Translations' mod='translatools'}</button>
	</form>
</div>

<script>
	$(document).ready(function(){
		$('button[data-confirm]').each(function(){
			var my = $(this);
			if(my.attr('onclick'))
			{
				my.attr('data-onclick', my.attr('onclick'));
				my.attr('onclick', '');
			}
		});

		$('button[data-confirm]').click(function(e){
			var originalButton = $(this);

			// A label may click on the button :)
			// abort if button is already hidden.
			if(!originalButton.is(':visible'))
			{
				event.preventDefault();
				return;
			}

			var container = originalButton.closest('span.confirm');

			originalButton.hide();

			var actionButton = $('<button class="btn btn-danger"></button>')
			.attr('name', originalButton.attr('name'))
			.attr('value', originalButton.attr('value'))
			.html(originalButton.attr('data-confirm'))
			.appendTo(container);

			if (originalButton.attr('data-onclick'))
			{
				actionButton.attr('onclick', originalButton.attr('data-onclick'));
			}

			var cancelButton = $('<button class="btn btn-success"></button>')
			.html(originalButton.attr('data-cancel') || 'Cancel')
			.appendTo(container);

			var buttons = [actionButton, cancelButton];

			for (var i in buttons)
			{
				buttons[i].on('click', function(){
					originalButton.show();
					actionButton.remove();
					cancelButton.remove();
				});
			}


			e.preventDefault();
		});

		$('input[name=jipt_fo]').change(function(){
			$.post('{$link->getAdminLink("AdminTranslatools")}&action=setConfigurationValue&key=JIPT_FO&value='+$(this).val());
			event.preventDefault();
		});

		$('input[name=jipt_bo]').change(function(){
			$.post('{$link->getAdminLink("AdminTranslatools")}&action=setConfigurationValue&key=JIPT_BO&value='+$(this).val());
			event.preventDefault();
		});
	});

	function updateConfigValue(input_id)
	{
		$.post('{$link->getAdminLink("AdminTranslatools")}&action=setConfigurationValue&key='+encodeURIComponent(input_id)+'&value='+encodeURIComponent($('#'+input_id).val()));
		event.preventDefault();
	};

	function performMultiStepAjaxAction(action, payload, fdbk, handler, success_id)
	{
		var url = '{$link->getAdminLink("AdminTranslatools")}&action='+action+'&ajax=1';
		var span_id = success_id ? ' id="'+success_id+'" ' : '';
		$.ajax({
			type: "POST",
			url: url,
			data: JSON.stringify(payload),
			success: function(data){
				if (handler)
				{
					handler(data);
				}
				if(data.success)
				{
					fdbk.html('<span class="success">'+(data.message || "Ok")+'</span>');
					if(data['next-action'])
					{
						fdbk.html(fdbk.html()+'&nbsp;<span class="neutral">...</span>');

						performMultiStepAjaxAction(data['next-action'], data['next-payload'], fdbk, handler, success_id);
					}
					else
					{
						fdbk.html("<span "+span_id+" class='success'>"+(data.message || "Done!")+"</span>")
					}
				}
				else
				{
					fdbk.html('<span class="error">'+(data.message || "An unspecified error occured.")+'</span>');
				}
			},
			dataType: 'json'
		});
	};

	function exportSourcesToCrowdin(dontPreventDefault)
	{
		var fdbk = $('#export-to-crowdin-feedback');
		fdbk.html('');
		performMultiStepAjaxAction('exportSources', {}, fdbk, null, 'sources-successfully-exported');

		if (!dontPreventDefault)
		{
			event.preventDefault();
		}
	};

	function exportTranslationsToCrowdin()
	{
		var fdbk = $('#export-translations-to-crowdin-feedback');
		fdbk.html('');
		performMultiStepAjaxAction('exportTranslations', {
			language: $('#export-translations-language').val()
		}, fdbk);
		event.preventDefault();
	}

	function handleDownloadTranslationsReturn(data)
	{
		var fdbk = $('#download-from-crowdin-feedback');

		if(data.success)
		{
			fdbk.html('<span id="translations-downloaded" data-success="1" class="success">'+data.message+'</span>');
		}
		else
		{
			fdbk.html('<span id="translations-downloaded" data-success="0" class="error">'+data.message+'</span>');
		}
	};

	function downloadTranslationsFromCrowdin(dontPreventDefault)
	{
		$('#download-from-crowdin-feedback').html('<span class="neutral">Downloading...</span>');

		$.ajax({
		  type: "POST",
		  url: '{$link->getAdminLink("AdminTranslatools")}&action=downloadTranslations&ajax=1',
		  data: JSON.stringify({
		  	language: $('#install_language').val()
		  }),
		  success: handleDownloadTranslationsReturn,
		  dataType: 'json'
		});
		if (!dontPreventDefault)
		{
			event.preventDefault();
		}
	};

	function regenerateCrowdinTranslations(dontPreventDefault)
	{
		var fdbk = $('#regenerate-translations-feedback');
		fdbk.html('<span class="neutral">Regenerating, can take a while... navigating away from this page won\'t stop the process.</span>');

		var url = "http://api.crowdin.net/api/project/{$CROWDIN_PROJECT_IDENTIFIER}/export?key={$CROWDIN_PROJECT_API_KEY}"

		$.ajax({
		  type: "GET",
		  url: url,
		  data: {
		  	jsonp: 'handleRegenerateTranslations'
		  },
		  dataType: 'jsonp'
		});

		if (!dontPreventDefault)
		{
			event.preventDefault();
		}
	};

	function handleRegenerateTranslations(data)
	{
		var fdbk = $('#regenerate-translations-feedback');
		if (data.success)
		{
			if (data.success.status === 'skipped')
			{
				fdbk.html('<span id="regeneration-done" data-success="2" class="neutral">Regeneration refused by Crowdin: can only be done every 30 minutes through the API.</span>');
			}
			else if (data.success.status === 'built')
			{
				fdbk.html('<span id="regeneration-done" data-success="1" class="success">Done :)</span>');
			}
			else
			{
				fdbk.html('<span id="regeneration-done" data-success="0" class="error">Maybe it worked, but return code is unknown to me.</span>');
			}
		}
		else
		{
			fdbk.html('<span id="regeneration-done" data-success="0" class="error">Something wrong happened, sorry.</span>');
		}
	};

</script>
