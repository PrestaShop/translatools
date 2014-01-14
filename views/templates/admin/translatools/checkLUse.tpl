<table class="table">
	<tr><th>File</th><th>Issue</th><th>#problems</th></tr>
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
