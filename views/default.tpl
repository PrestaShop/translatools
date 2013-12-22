<div class='alert alert-info'>
	Here are some tools to check that translations in your shop follow the guidelines.
</div>

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