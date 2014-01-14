<form>
	{if $live_translation_enabled == 1}
		<button onclick="javascript:toggleLiveTranslation(false);" type="button" class="btn btn-default">
			Disable JIPT		
		</button>
	{else}
		<button onclick="javascript:toggleLiveTranslation(true);" type="button" class="btn btn-success">
			Enable JIPT
		</button>
	{/if}
</form>

<script>
	function toggleLiveTranslation(yesno)
	{
		$.ajax({
			type: 'POST',
			url: '{$translatools_controller}&action=switchVirtualLanguage',
			data: JSON.stringify({
				value: yesno
			}),
			dataType: 'json',
			success: function(resp){
				if (resp.success)
				{
					location.reload();
				}
			}
		});
	}
</script>