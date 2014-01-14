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
		<div class="col-md-2"><label>Overriden file:</label></div>
		<div class="col-md-10">{$delta.overriden_file}</div>
	</div>
	<div class="row">
		<div class="col-md-2"><label>Original file:</label></div>
		<div class="col-md-10">{$delta.original_file}</div>
	</div>
	<span class="label label-info">Strings that are in the overriden file but not in the original one</span>
	<div class="strings">
		<ul>
			{foreach from=$delta.differences item=string}
				{if is_array($string)}
					<li>{$string.overriden} &lt;=> {$string.original}</li>
				{else}
					<li>{$string}</li>
				{/if}
			{/foreach}
		</ul>
	</div>
</div>
{/foreach}