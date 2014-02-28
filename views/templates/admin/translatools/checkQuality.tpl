<div class="panel">
	<h3>Strings containing tags - {$with_tags|count}</h3>
	{foreach from=$with_tags item=str}
		<div class="row">
			<div class="col-lg-12">
				{$str|escape:'html':'UTF-8'}
			</div>
		</div>
	{/foreach}
</div>