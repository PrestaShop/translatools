<form>
	{if $live_translation_enabled == 1}
		<button onclick="javascript:toggleLiveTranslation(false);" type="button" class="btn btn-default">
			{l s='Disable Live Translation' mod='translatools'}		
		</button>
	{else}
		<button onclick="javascript:toggleLiveTranslation(true);" type="button" class="btn btn-success">
			{l s='Enable Live Translation' mod='translatools'}
		</button>
	{/if}
</form>

<script>
	function toggleLiveTranslation(yesno)
	{
		$.ajax({
			type: 'POST',
			url: '{$link->getAdminLink("AdminTranslatools")}&action=switchVirtualLanguage&ajax=1',
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
