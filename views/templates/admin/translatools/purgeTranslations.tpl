<style>
	div.killed
	{
		margin-left: 15px;
	}
</style>

<p><span class="label label-info">{l s='The following files were deleted:' mod='translatools'}</span></p>
{foreach from=$killed item=path}
	<div class="killed">
		<span>{$path|escape:'htmlall':'UTF-8'}</span>
	</div>
{/foreach}
