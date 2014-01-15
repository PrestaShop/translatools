{* Like the good mathematician, the good programmer is lazy. *}
{function yesno label="Yes or No?" input_name="yes_or_no" value_on="1" value_off="0" label_on="Yes" label_off="No" value="1"}
	{assign var=id_on value=$input_name|replace:'[':'_'|replace:']':'_'|cat:'_on'}
	{assign var=id_off value=$input_name|replace:'[':'_'|replace:']':'_'|cat:'_off'}

	<div class="form-group">
		<label for="{$id_on}" class="control-label col-lg-3">{$label|escape:'htmlall':'UTF-8'}</label>
		<div class="col-lg-2">
			<div class="input-group">
				<span class="switch prestashop-switch">
					<input name="{$input_name}" type="radio" id="{$id_on}" value="{$value_on}"{if $value==$value_on} checked{/if}>
					<label for="{$id_on}">
						{$label_on|escape:'htmlall':'UTF-8'}
					</label>
					<input name="{$input_name}" type="radio" id="{$id_off}" value="{$value_off}"{if $value==$value_off} checked{/if}>
					<label for="{$id_off}">
						{$label_off|escape:'htmlall':'UTF-8'}
					</label>
					<a class="slide-button btn"></a>
				</span>
			</div>
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
	<p><strong>Warning:</strong> This module should never be used on a production shop!</p>
</div>

{if $non_writable_directories|count > 0}
	<div class="alert alert-warning">
		<p><strong>Warning:</strong> Some folders in which this module may need to create files cannot be written to. This will likely prevent you from using the module to its full potential.</p>
		<p>Please try to adjust file permissions so that your web server may be able to create the following directories (if they do not exist) and write into them:</p>
		<ul>
			{foreach from=$non_writable_directories item=dir}
				<li>{$dir}</li>
			{/foreach}
		</ul>
	</div>
{/if}

<div class="panel">
	<h3>Export translations</h3>

	<form class="form-horizontal" action="{$link->getAdminLink('AdminTranslatools')}" method="POST">
				
		{yesno label="Export Front-Office Strings" input_name="section[frontOffice]"}
		
		{yesno label="Export Back-Office Strings" input_name="section[backOffice]"}

		{yesno label="Export Module Strings" input_name="section[modules]"}
	
		<div class="form-group">
			<label class="control-label col-lg-3" for="filter_modules">Which modules to parse?</label>
			<div class="col-lg-6">
				<select name="filter_modules" id="filter_modules">
					<option value="native" selected>Native</option>
					<option value="all">All</option>
				</select>
			</div>
		</div>
	
		{if $modules_not_found_warning}
			<div class="row">
				<div class="col-lg-3"></div>
				<div class="col-lg-6">
					<div class="alert alert-warning">
						<strong>Warning: </strong>{$modules_not_found_warning|escape:'htmlall':'UTF-8'}	
					</div>
				</div>
			</div>
		{/if}

		<div class="form-group">
			<label class="control-label col-lg-3" for="overriden_modules">Which types of modules to parse?</label>
			<div class="col-lg-6">
				<select name="overriden_modules" id="overriden_modules">
					<option value="both">Core and Overriden</option>
					<option value="core">Core Only</option>
					<option value="overriden">Overriden Only</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-lg-3" for="modules_storage">Where to store their translations?</label>
			<div class="col-lg-6">
				<select name="modules_storage" id="modules_storage">
					<option value="core">All in core</option>
					<option value="theme">Each in its place</option>
				</select>
			</div>
		</div>

		{yesno label="Export Errors Strings" input_name="section[errors]"}
		{yesno label="Export PDFs Strings" input_name="section[pdfs]"}
		{yesno label="Export Tabs Strings" input_name="section[tabs]"}
		{yesno label="Export Mail Subject Strings" input_name="section[mailSubjects]"}
		{yesno label="Export Mail Content Strings" input_name="section[mailContent]"}
		{yesno label="Export Generated Emails" input_name="section[generatedEmails]"}

		
		<div class="form-group">
			<label class="control-label col-lg-3" for="theme">Theme</label>
			<div class="col-lg-6">
				<select name="theme" id="theme">
					{foreach from=$themes item=theme}
						<option value="{$theme}">{$theme|escape:'htmlall':'UTF-8'}</option>
					{/foreach}
				</select>
			</div>
		</div>
		<div class="form-group">
			<label for="language" class="control-label col-lg-3">Export language</label>
			<div class="col-lg-6">
					<select name="language" id="language">
					<option value="-">As in code (should be English)</option>
					{foreach from=$languages item=language key=code}
						<option value="{$code}">{$language|escape:'htmlall':'UTF-8'}</option>
					{/foreach}
				</select>
			</div>
		</div>
		<div class="form-group">
			<div class="col-lg-3"></div>
			<div class="col-lg-9">
				<button  class="btn btn-primary" name="action" value="exportTranslations">Export Now</button>
				<button  class="btn btn-primary" name="action" value="viewStats">View Stats</button>
			</div>
		</div>
	</form>
</div>

<div class="panel">
	<h3>Crowdin API</h3>
	<div class="alert alert-info">
		<p>Only the project idenfifier is required here (to use Just In Place Translation). The default value should be fine.</p>
		<p>If you are not an administrator of the PrestaShop Crowdin project you should not need to worry about the API Key and leave it blank. It is used to unlock advanced features that are not needed by translators.</p>
	</div>
	<form action="{$link->getAdminLink('AdminTranslatools')}&amp;action=default" method="POST" class="form-horizontal">
		<input type="hidden" name="update_api_settings" value="1">
		<div class="form-group">
			<label class="control-label col-lg-3" for="CROWDIN_PROJECT_IDENTIFIER">Project Identifier</label>
			<div class="col-lg-9">
				<div class="row">
					<div class="col-lg-8"><input class="form-control" value="{$CROWDIN_PROJECT_IDENTIFIER}" id="CROWDIN_PROJECT_IDENTIFIER" name="CROWDIN_PROJECT_IDENTIFIER" type="text" placeholder="prestashop-test-api"></div>
				</div>
				
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-lg-3" for="CROWDIN_PROJECT_API_KEY">Project API Key</label>
			<div class="col-lg-9">
				<div class="row">
					<div class="col-lg-8"><input class="form-control" value="{$CROWDIN_PROJECT_API_KEY}" id="CROWDIN_PROJECT_API_KEY" name="CROWDIN_PROJECT_API_KEY" type="text" placeholder="a2f1g5e8a6b7d4g5e2c1234a5e6f8c33"></div>
				</div>
			</div>
		</div>
		<div class="form-group">
			<div class="col-lg-3"></div>
			<div class="col-lg-8">
				<button class="btn btn-default">Save</button>
			</div>
		</div>
	</form>
</div>

<div class="panel">
	<h3>Crowdin Integration</h3>
	
	<form class="form-horizontal">
		{yesno input_name=jipt_bo label="Enable Crowdin-JIPT in Back-Office" value=$jipt_bo}
		{yesno input_name=jipt_fo label="Enable Crowdin-JIPT in Front-Office" value=$jipt_fo}
	</form>
	
	<div class="row">
		<div class="col-lg-3"></div>
		<div class="col-lg-6">
			<p class="help-block">JIPT Virtual Language is set to <strong>{$jipt_language}</strong>.</p>
			{if !isset($languages[$jipt_language])}
				<form method="POST">{$translatools_stay_here}<p class="alert alert-info">The virtual language was not created on this shop though, you need to <button name="action" value="createVirtualLanguage" class="btn btn-primary">Create It</button> before you can use Crowdin-JIPT.</p></form>
			{/if}
		</div>
	</div>
	
	{if isset($CROWDIN_PROJECT_API_KEY) and ($CROWDIN_PROJECT_API_KEY != '')}
		<form class="form-horizontal">
			<div class="form-group">
				<label for="export" class="control-label col-lg-3">Export Sources to Crowdin</label>
				<div class="col-lg-6">
					<div class="row">
						<div class="col-lg-4">
							<span class="confirm">
								<button type="button" data-confirm="Are you sure?" data-cancel="No" onclick="javascript:exportSourcesToCrowdin();" id="export" class="btn btn-warning">Export!</button>
							</span>
						</div>
						<div class="col-lg-8">
							<p class="form-control-static" id="export-to-crowdin-feedback"></p>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-3"></div>
				<div class="col-lg-6">
					<div class="alert alert-warning">
						<p>You need to export the 'As in code' language before pushing to Crowdin.</p>
						<p>Exporting the 'As in code' language will download an archive containing all the strings selected for export. This is so that you can review what is exported, but, if you are satisfied with it, you don't need to do anything more with this file.</p>
					</div>
				</div>
			</div>
		</form>

		<form class="form-horizontal">
			<div class="form-group">
				<label for="export-translations-to-crowdin" class="control-label col-lg-3">Export Translations to Crowdin</label>
				<div class="col-lg-6">
					<div class="row">
						<div class="col-lg-2">
							<select name="language" id="export-translations-language">
								<option value="*">All languages</option>
								{foreach from=$languages item=language key=code}
									<option value="{$code}">{$language|escape:'htmlall':'UTF-8'}</option>
								{/foreach}
							</select>
						</div>
						<div class="col-lg-4">
							<span class="confirm">
								<button type="button" data-confirm="Are you sure?" data-cancel="No" onclick="javascript:exportTranslationsToCrowdin();" id="export-translations-to-crowdin" class="btn btn-warning">Export!</button>
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
				<label for="export" class="control-label col-lg-3">Regenerate Crowdin Translations</label>
				<div class="col-lg-6">
					<div class="row">
						<div class="col-lg-4">
							<span class="confirm">
								<button type="button" data-confirm="Sure?" data-cancel="Well, no thanks." onclick="javascript:regenerateCrowdinTranslations();" class="btn btn-default">Regenerate!</button>
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
			<label for="export" class="control-label col-lg-3">Download translations from Crowdin</label>
			<div class="col-lg-6">
				<div class="row">
					<div class="col-lg-4">
						<span class="confirm">
							<button data-confirm="Sure?" data-cancel="Nope." type="button" onclick="javascript:downloadTranslationsFromCrowdin();" class="btn btn-primary">Download!</button>
						</span>
					</div>
					<div class="col-lg-8 feedback">
						<p class="form-control-static" id="download-from-crowdin-feedback"></p>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-3"></div>
			<div class="col-lg-6">
				<div class="alert alert-info">
						<p>Downloading the translations from crowdin will install the latest translation files from Crowdin onto your Shop.</p>
						<p>You should run this action regularly during pre release times if you want to be able to use JIPT on all the strings in PrestaShop.</p>
					</div>
				<div class="alert alert-warning">
					<p><strong>Warning:</strong> Downloading translations from Crowdin will overwrite all or most of your own translations, in all languages. Only proceed if you really know what you're doing!</p>
					<p>This action will install translation files for all supported languages, even if they are not installed on your shop. So this will create a lot of files!</p>
				</div>
			</div>
		</div>
	</form>
</div>

<div class="panel">
	<h3>Translation Linting</h3>
	<form action="{$link->getAdminLink('AdminTranslatools')}&amp;action=checkCoherence" method="POST" class="form-horizontal">
		<div class="form-group">
			<label for="check-coherence" class="control-label col-lg-3">Check Overriding Coherence</label>
			<div class="col-lg-6">
				<button type="submit" class="btn btn-primary">Check!</button>
			</div>
		</div>
	</form>
	
	<form method="POST" action="{$link->getAdminLink('AdminTranslatools')}&amp;action=checkLUse" class="form-horizontal">
		<div class="form-group">
			<label class="control-label col-lg-3" for="check_l">Check use of translation functions</label>
			<div class="col-lg-6">
				<select name="theme" id="theme_lint">
					{foreach from=$themes item=theme}
						<option value="{$theme}">{$theme}</option>
					{/foreach}
				</select>
			</div>
			<div class="col-lg-3">
				<button class="btn" id="check_l">Check!</button>
			</div>
		</div>
	</form>
</div>

<div class="panel">
	<h3>Purge Translations</h3>
	<div class='alert alert-warning'>
		This will delete all translation files on your shop (except e-mails).
	</div>
	<form action="{$link->getAdminLink('AdminTranslatools')}&amp;action=purgeTranslations" method="POST">
		<button onclick="javascript:return confirm('Really purge all translations??');" class="btn btn-warning">Purge Translations</button>
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

	function performMultiStepAjaxAction(action, payload, fdbk, handler)
	{
		var url = '{$link->getAdminLink("AdminTranslatools")}&action='+action+'&ajax=1';

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

						performMultiStepAjaxAction(data['next-action'], data['next-payload'], fdbk, handler);
					}
					else
					{
						fdbk.html("<span class='success'>"+(data.message || "Done!")+"</span>")
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

	function exportSourcesToCrowdin()
	{
		var fdbk = $('#export-to-crowdin-feedback');
		fdbk.html('');
		performMultiStepAjaxAction('exportSources', {}, fdbk);
		event.preventDefault();
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
			fdbk.html('<span class="success">'+data.message+'</span>');
		}
		else
		{
			fdbk.html('<span class="error">'+data.message+'</span>');
		}
	};

	function downloadTranslationsFromCrowdin()
	{
		$('#download-from-crowdin-feedback').html('<span class="neutral">Downloading...</span>');

		$.ajax({
		  type: "POST",
		  url: '{$link->getAdminLink("AdminTranslatools")}&action=downloadTranslations&ajax=1',
		  success: handleDownloadTranslationsReturn,
		  dataType: 'json'
		});
		event.preventDefault();
	};

	function regenerateCrowdinTranslations()
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

		event.preventDefault();
	};

	function handleRegenerateTranslations(data)
	{
		var fdbk = $('#regenerate-translations-feedback');
		if (data.success)
		{
			if (data.success.status === 'skipped')
			{
				fdbk.html('<span class="neutral">Regeneration refused by Crowdin: can only be done every 30 minutes through the API.</span>');
			}
			else if (data.success.status === 'built')
			{
				fdbk.html('<span class="success">Done :)</span>');
			}
			else
			{
				fdbk.html('<span class="error">Maybe it worked, but return code is unknown to me.</span>');
			}
		}
		else
		{
			fdbk.html('<span class="error">Something wrong happened, sorry.</span>');
		}
	};

</script>