<style>
	div.killed
	{
		margin-left: 15px;
	}
</style>

<p><span class="label label-info">The following files were deleted:</span></p>
{foreach from=$killed item=path}
	<div class="killed">
		<span>{$path}</span>
	</div>
{/foreach}