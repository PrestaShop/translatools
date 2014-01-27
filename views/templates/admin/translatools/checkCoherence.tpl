<style>
	div.delta
	{
		margin: 10px;
		background-color: white;
		padding: 10px;
		border-radius: 10px;
		border: 1px solid #CCC;
	}
	div.strings
	{
		margin-top: 10px;
	}
</style>

{foreach from=$deltas item=delta}
<div class="delta">
	<div class="row">
		<div class="col-md-2"><label>{l s='Overriden file:' mod='translatools'}</label></div>
		<div class="col-md-10">{$delta.overriden_file|escape:'htmlall':'UTF-8'}</div>
	</div>
	<div class="row">
		<div class="col-md-2"><label>{l s='Original file:' mod='translatools'}</label></div>
		<div class="col-md-10">{$delta.original_file|escape:'htmlall':'UTF-8'}</div>
	</div>
	<span class="label label-info">{l s='Strings that are in the overriden file but not in the original one' mod='translatools'}</span>
	<div class="strings">
		<ul>
			{foreach from=$delta.differences item=string}
				{if is_array($string)}
					<li>{$string.overriden|escape:'htmlall':'UTF-8'} &lt;=> {$string.original|escape:'htmlall':'UTF-8'}</li>
				{else}
					<li>{$string|escape:'htmlall':'UTF-8'}</li>
				{/if}
			{/foreach}
		</ul>
	</div>
</div>
{/foreach}
