<table class="table">
	<tr><th>{l s='File' mod='translatools'}</th><th>{l s='Issue' mod='translatools'}</th><th>{l s='#problems' mod='translatools'}</th></tr>
	{foreach from=$issues key=file item=problems}
		{foreach from=$problems key=problem item=n}
			<tr>
				<td>{$file|escape:'htmlall':'UTF-8'}</td>
				<td>{$problem|escape:'htmlall':'UTF-8'}</td>
				<td>{$n|escape:'htmlall':'UTF-8'}</td>
			</tr>
		{/foreach}
	{/foreach}	
</table>
