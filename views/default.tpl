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
		<button class="btn btn-primary">Export Now</button>
	</form>
</div>