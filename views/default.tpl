<div class="panel">
	<h3>Translation overriding coherence</h3>
	<div class='alert alert-info'>
		This will check that the strings used in overriden templates are the same as the ones in the original modules.
	</div>
	<form action="" method="GET">
		{$translacheck_stay_here}
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
		{$translacheck_stay_here}
		<input type="hidden" name="action" value="purgeTranslations">
		<span class="confirm">
			<button data-confirm="Really purge translations?" data-cancel="Oh no!" class="btn btn-warning">Purge Translations</button>
		</span>
	</form>
</div>

<div class="panel">
	<h3>Crowdin JIPT</h3>

	<div class="form-group">
		<label for="jipt_bo_on">Enable Crowdin-JIPT in Back-Office</label>
		<div class="row">
			<div class="input-group col-lg-2">
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
		<label for="jipt_fo_on">Enable Crowdin-JIPT in Front-Office</label>
		<div class="row">
			<div class="input-group col-lg-2">
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
		<p>JIPT Virtual Language is set to <strong>{$jipt_language}</strong>.</p>
		{if !isset($languages[$jipt_language])}
			<p>The virtual language was not created on this shop though, you need to create it before you can use Crowdin-JIPT.</p>
			<form method="POST">{$translacheck_stay_here}<button name="action" value="createVirtualLanguage" class="btn btn-success">Create It Now</button></form>
		{/if}
	</div>
</div>

<div class="panel">
	<h3>Export translations</h3>
	<form action="" method="GET">
		{$translacheck_stay_here}
		<div class="checkbox">
			<label for="front-office">
				<input name="section[]" id="front-office" type="checkbox" checked value="frontOffice">Front-Office
			</label>
		</div>
		<div class="checkbox">
			<label for="back-office">
				<input name="section[]" id="back-office" type="checkbox" checked value="backOffice">Back-Office
			</label>
		</div>
		<div class="form-group">
			<div class="checkbox">
				<label for="modules">
					<input name="section[]" id="modules" type="checkbox" checked value="modules">Modules
				</label>
			</div>
			<div class="form-goup">
				<label for="overriden_modules">Which modules to parse?
					<select style="width: 300px;" name="overriden_modules" id="overriden_modules" class="form-control">
						<option value="both">Core and Overriden</option>
						<option value="core">Core Only</option>
						<option value="overriden">Overriden Only</option>
					</select>
				</label>
			</div>
			<div class="form-goup">
				<label for="modules_storage">Where to store their translations?
					<select style="width: 300px;" name="modules_storage" id="modules_storage" class="form-control">
						<option value="core">All in core</option>
						<option value="theme">Each in its place</option>
					</select>
				</label>
			</div>
		</div>
		<div class="checkbox">
			<label for="errors">
				<input name="section[]" id="errors" type="checkbox" checked value="errors">Errors
			</label>
		</div>
		<div class="checkbox">
			<label for="pdfs">
				<input name="section[]" id="pdfs" type="checkbox" checked value="pdfs">PDFs
			</label>
		</div>
		<div class="checkbox">
			<label for="tabs">
				<input name="section[]" id="tabs" type="checkbox" checked value="tabs">Tabs
			</label>
		</div>
		<div class="form-group">
			<label for="theme"> Theme
				<select style="width: 300px;" name="theme" id="theme" class="form-control">
					{foreach from=$themes item=theme}
						<option value="{$theme}">{$theme}</option>
					{/foreach}
				</select>
			</label>
		</div>
		<div class="form-group">
			<label for="language"> Export language
				<select style="width: 300px;" name="language" id="language" class="form-control">
					<option value="-">As in code (should be English)</option>
					{foreach from=$languages item=language key=code}
						<option value="{$code}">{$language}</option>
					{/foreach}
				</select>
			</label>
		</div>
		<button class="btn btn-primary" name="action" value="exportTranslations">Export Now</button>
		<button class="btn btn-primary" name="action" value="viewStats">View Stats</button>
	</form>
</div>

<style>
	span.confirm>button
	{
		margin-right: 5px;
	}
</style>

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
			$.post('{$translacheck_url}&action=setConfigurationValue&key=JIPT_FO&value='+$(this).val());
		});

		$('input[name=jipt_bo]').change(function(){
			$.post('{$translacheck_url}&action=setConfigurationValue&key=JIPT_BO&value='+$(this).val());
		});
	})
</script>