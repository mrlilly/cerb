<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formPortalTemplatePeek" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="portal">
<input type="hidden" name="action" value="saveTemplatePeek">
<input type="hidden" name="id" value="{$template->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>{$template->path}:</b><br>
<textarea name="content" wrap="off" style="height:300px;width:98%;">{$template->content}</textarea><br>
<br>

{if $active_worker->is_superuser}
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !$disabled}
		{if $active_worker->is_superuser}<button type="button" onclick="if(confirm('Are you sure you want to revert this template to the default?')){literal}{{/literal}this.form.do_delete.value='1';genericAjaxPost('formPortalTemplatePeek', 'view{$view_id}', '');genericAjaxPopupClose('peek');{literal}}{/literal}"><span class="glyphicons glyphicons-refresh"></span></a> {'Revert'|devblocks_translate|capitalize}</button>{/if}
	{/if}
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>	
{/if}
<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Edit Custom Template");
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formPortalTemplatePeek', 'view{$view_id}', '', function() {
				genericAjaxPopupClose('peek');
			});
		});
	});
});
</script>