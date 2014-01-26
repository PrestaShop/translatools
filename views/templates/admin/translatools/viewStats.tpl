<table>
	<tr>
		<th>{l s='File' mod='translatools'}</th><th>{l s='Messages' mod='translatools'}</th>
	</tr>
	{foreach from=$stats item=data key=file}
		<tr>
			<td>{$file|escape:'htmlall':'UTF-8'}</td><td>{$data.total|escape:'htmlall':'UTF-8'}</td>
		</tr>
	{/foreach}
</table>
