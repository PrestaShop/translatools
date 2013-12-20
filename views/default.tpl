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
		<input type="hidden" name="action" value="exportTranslations">
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
		<div class="checkbox">
			<label for="modules">
				<input name="section[]" id="modules" type="checkbox" checked value="modules">Modules
			</label>
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
		<button class="btn btn-primary">Export Now</button>
	</form>
</div>