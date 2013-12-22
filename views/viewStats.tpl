<table>
	<tr>
		<th>File</th><th>Messages</th>
	</tr>
	{foreach from=$stats item=data key=file}
		<tr>
			<td>{$file}</td><td>{$data.total}</td>
		</tr>
	{/foreach}
</table>