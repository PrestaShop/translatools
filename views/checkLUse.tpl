<table class="table">
	<tr><th>File</th><th>Issue</th><th>#problems</th></tr>
	{foreach from=$issues key=file item=problems}
		{foreach from=$problems key=problem item=n}
			<tr>
				<td>{$file}</td>
				<td>{$problem}</td>
				<td>{$n}</td>
			</tr>
		{/foreach}
	{/foreach}	
</table>
