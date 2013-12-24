<style>
	select.my-input, input[type=text].my-input
	{
		width: 300px;
	}
	span.confirm>button
	{
		margin-right: 5px;
	}
</style>

<div class="panel">
	<h3>Translation overriding coherence</h3>
	<div class='alert alert-info'>
		This will check that the strings used in overriden templates are the same as the ones in the original modules.
	</div>
	<form action="" method="GET">
		{$translatools_stay_here}
		<input type="hidden" name="action" value="checkCoherence">
		<button class="btn btn-primary">Check Now</button>
	</form>
</div>

<div class="panel">
	<h3>Purge Translations</h3>
	<div class='alert alert-warning'>
		This will delete all translation files on your shop (except e-mails).
	</div>
	<form action="" method="GET">
		{$translatools_stay_here}
		<input type="hidden" name="action" value="purgeTranslations">
		<span class="confirm">
			<button data-confirm="Really purge translations?" data-cancel="Oh no!" class="btn btn-warning">Purge Translations</button>
		</span>
	</form>
</div>

<div class="panel">
	<h3>Crowdin</h3>
	
	<form class="form-horizontal">
		<div class="form-group">
			<label for="jipt_bo_on" class="control-label col-lg-3">Enable Crowdin-JIPT in Back-Office</label>
			<div class="col-lg-2">
				<div class="input-group">
					<span class="switch prestashop-switch">
						<input name="jipt_bo" type="radio" id="jipt_bo_on" value="1"  {if $jipt_bo == '1'} checked {/if}>
						<label for="jipt_bo_on">
							<i class="icon-check-sign color_success"></i> Yes
						</label>
						<input name="jipt_bo" type="radio" id="jipt_bo_off" value="0"  {if $jipt_bo != '1'} checked {/if}>
						<label for="jipt_bo_off">
							<i class="icon-ban-circle color_danger"></i> No
						</label>
						<a class="slide-button btn btn-default"></a>
					</span>
				</div>
			</div>
		</div>
		
		<div class="form-group">
			<label for="jipt_fo_on" class="control-label col-lg-3">Enable Crowdin-JIPT in Front-Office</label>
			<div class="col-lg-2">
				<div class="input-group">
					<span class="switch prestashop-switch">
						<input name="jipt_fo" type="radio" id="jipt_fo_on" value="1" {if $jipt_fo == '1'} checked {/if}>
						<label for="jipt_fo_on">
							<i class="icon-check-sign color_success"></i> Yes
						</label>
						<input name="jipt_fo" type="radio" id="jipt_fo_off" value="0" {if $jipt_fo != '1'} checked {/if}>
						<label for="jipt_fo_off">
							<i class="icon-ban-circle color_danger"></i> No
						</label>
						<a class="slide-button btn btn-default"></a>
					</span>
				</div>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-lg-3" for="CROWDIN_PROJECT_IDENTIFIER">Project Identifier</label>
			<div class="col-lg-9">
				<div class="row">
					<div class="col-lg-8"><input class="form-control" value="{$CROWDIN_PROJECT_IDENTIFIER}" id="CROWDIN_PROJECT_IDENTIFIER" type="text" placeholder="prestashop-test-api"></div>
					<div class="col-lg-4"><button onclick="javascript:updateConfigValue('CROWDIN_PROJECT_IDENTIFIER');" type="button" class="btn btn-success"><i class="icon-ok"></i> Save</button></div>
				</div>
				
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-lg-3" for="CROWDIN_PROJECT_API_KEY">Project Identifier</label>
			<div class="col-lg-9">
				<div class="row">
					<div class="col-lg-8"><input class="form-control" value="{$CROWDIN_PROJECT_API_KEY}" id="CROWDIN_PROJECT_API_KEY" type="text" placeholder="a2f1g5e8a6b7d4g5e2c1234a5e6f8c33"></div>
					<div class="col-lg-4"><button onclick="javascript:updateConfigValue('CROWDIN_PROJECT_API_KEY');" type="button" class="btn btn-success"><i class="icon-ok"></i> Save</button></div>
				</div>
			</div>
		</div>
	</form>

	<div class="row">
		<div class="col-lg-3"></div>
		<div class="col-lg-6">
			<p class="help-block">JIPT Virtual Language is set to <strong>{$jipt_language}</strong>.</p>
			{if !isset($languages[$jipt_language])}
				<p class="alert alert-info">The virtual language was not created on this shop though, you need to create it before you can use Crowdin-JIPT.</p>
				<form method="POST">{$translatools_stay_here}<button name="action" value="createVirtualLanguage" class="btn btn-success">Create It Now</button></form>
			{/if}
		</div>
	</div>
	
	<form class="form-horizontal">
		<div class="form-group">
			<label for="export" class="control-label col-lg-3">Export Sources to Crowdin</label>
			<div class="col-lg-6">
				<div class="row">
					<div class="col-lg-2">
						<button onclick="javascript:exportSourcesToCrowdin();" id="export" class="btn btn-warning">Export!</button>
					</div>
					<div class="col-lg-10">
						<p class="form-control-static" id="export-to-crowdin-feedback"></p>
					</div>
				</div>
			</div>
		</div>
	</form>

</div>

<div class="panel">
	<h3>Export translations</h3>
	<form class="form-horizontal" action="" method="GET">
		{$translatools_stay_here}
		<div class="form-group">
			<label class="control-label col-lg-3" for="front-office">Export Front-Office Strings</label>
			<div class="checkbox col-lg-9">
				<input name="section[]" id="front-office" type="checkbox" checked value="frontOffice">
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-lg-3" for="back-office">Export Back-Office Strings</label>
			<div class="checkbox col-lg-9">
				<input name="section[]" id="back-office" type="checkbox" checked value="backOffice">
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-lg-3" for="modules">Export Modules Strings</label>
			<div class="checkbox col-lg-9">
				<input name="section[]" id="modules" type="checkbox" checked value="modules">
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-lg-3" for="overriden_modules">Which modules to parse?</label>
			<select class="col-lg-6" name="overriden_modules" id="overriden_modules">
				<option value="both">Core and Overriden</option>
				<option value="core">Core Only</option>
				<option value="overriden">Overriden Only</option>
			</select>
		</div>
		<div class="form-group">
			<label class="control-label col-lg-3" for="modules_storage">Where to store their translations?</label>
			<select class="col-lg-6" name="modules_storage" id="modules_storage">
				<option value="core">All in core</option>
				<option value="theme">Each in its place</option>
			</select>
		</div>

		<div class="form-group">
			<label class="control-label col-lg-3" for="errors">Export Errors Strings</label>
			<div class="checkbox col-lg-9">
				<input name="section[]" id="errors" type="checkbox" checked value="errors">
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-lg-3" for="pdfs">Export PDFs Strings</label>
			<div class="checkbox col-lg-9">
				<input name="section[]" id="pdfs" type="checkbox" checked value="pdfs">
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-lg-3" for="pdfs">Export Tabs Strings</label>
			<div class="checkbox col-lg-9">
				<input name="section[]" id="tabs" type="checkbox" checked value="tabs">
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-lg-3" for="theme">Theme</label>
			<select name="theme" id="theme" class="col-lg-6">
				{foreach from=$themes item=theme}
					<option value="{$theme}">{$theme}</option>
				{/foreach}
			</select>
		</div>
		<div class="form-group">
			<label for="language" class="control-label col-lg-3">Export language</label>
			<select class="my-input" name="language" id="language" class="col-lg-6">
				<option value="-">As in code (should be English)</option>
				{foreach from=$languages item=language key=code}
					<option value="{$code}">{$language}</option>
				{/foreach}
			</select>
		</div>
		<div class="form-group">
			<div class="col-lg-3"></div>
			<div class="col-lg-9">
				<button class="btn btn-primary" name="action" value="exportTranslations">Export Now</button>
				<button class="btn btn-primary" name="action" value="viewStats">View Stats</button>
			</div>
		</div>
	</form>
</div>

<script>
	$(document).ready(function(){
		$('button[data-confirm]').click(function(e){
			var originalButton = $(this);
			var container = originalButton.closest('span.confirm');
			
			originalButton.hide();

			var actionButton = $('<button class="btn btn-danger"></button>')
			.attr('name', originalButton.attr('name'))
			.attr('value', originalButton.attr('value'))
			.html(originalButton.attr('data-confirm'))
			.appendTo(container);

			var cancelButton = $('<button class="btn btn-success"></button>')
			.html(originalButton.attr('data-cancel') || 'Cancel')
			.appendTo(container);

			cancelButton.on('click', function(){
				originalButton.show();
				actionButton.remove();
				cancelButton.remove();
			});

			e.preventDefault();
		});

		$('input[name=jipt_fo]').change(function(){
			$.post('{$translatools_url}&action=setConfigurationValue&key=JIPT_FO&value='+$(this).val());
			event.preventDefault();
		});

		$('input[name=jipt_bo]').change(function(){
			$.post('{$translatools_url}&action=setConfigurationValue&key=JIPT_BO&value='+$(this).val());
			event.preventDefault();
		});
	});

	function updateConfigValue(input_id)
	{
		$.post('{$translatools_url}&action=setConfigurationValue&key='+encodeURIComponent(input_id)+'&value='+encodeURIComponent($('#'+input_id).val()));
		event.preventDefault();
	}

	function exportSourcesToCrowdin()
	{
		var fdbk = $('#export-to-crowdin-feedback');

		var firstActionURL = '{$translatools_controller}&action=exportSources';

		$.ajax({
		  type: "POST",
		  url: firstActionURL,
		  success: handleExportSourcesReturn,
		  dataType: 'json'
		});

		event.preventDefault();
	}

	function handleExportSourcesReturn(data)
	{
		var fdbk = $('#export-to-crowdin-feedback');
		console.log(data);
		if(data.success)
		{
			fdbk.html('<span class="success">'+data.message+'</span>');
			if(data['next-action'])
			{
				fdbk.html(fdbk.html()+'&nbsp;<span class="neutral">Processing next step...</span>');

				var nextActionURL = '{$translatools_controller}&action='+data['next-action'];
				$.ajax({
				  type: "POST",
				  url: nextActionURL,
				  data: JSON.stringify(data['next-payload']),
				  success: handleExportSourcesReturn,
				  dataType: 'json'
				});
			}
			else
			{
				fdbk.html("<span class='success'>Done!</span>")
			}
		}
		else
		{
			fdbk.html('<span class="error">'+data.message+'</span>');
		}
	}
</script>