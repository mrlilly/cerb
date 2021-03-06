{$uniqid = uniqid()}
<div id="{$uniqid}">
	<div class="tester"></div>
	
	<button type="button" class="cerb-popupmenu-trigger" onclick="">Insert placeholder &#x25be;</button>
	<button type="button" class="tester">{'common.test'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="genericAjaxPopup('help', 'c=internal&a=showSnippetHelpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');">Help</button>
	
	{function tree level=0}
		{foreach from=$keys item=data key=idx}
			{if is_array($data->children) && !empty($data->children)}
				<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
					{if $data->key}
						<div style="font-weight:bold;">{$data->l|capitalize}</div>
					{else}
						<div>{$idx|capitalize}</div>
					{/if}
					<ul>
						{tree keys=$data->children level=$level+1}
					</ul>
				</li>
			{elseif $data->key}
				<li data-token="{$data->key}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
			{/if}
		{/foreach}
	{/function}
	
	<ul class="menu" style="width:150px;">
	{tree keys=$placeholders}
	</ul>
</div>

<script type="text/javascript">
$(function() {
	var $div = $('#{$uniqid}');
	var $toolbar = $div.parent();
	var $popup = genericAjaxPopupFind($div);
	
	// Placeholder menu
	
	var $placeholder_menu_trigger = $toolbar.find('button.cerb-popupmenu-trigger');
	var $placeholder_menu = $toolbar.find('ul.menu').hide();
	
	// Quick insert token menu
	
	$placeholder_menu.menu({
		select: function(event, ui) {
			var token = ui.item.attr('data-token');
			var label = ui.item.attr('data-label');
			
			if(undefined == token || undefined == label)
				return;
			
			var $field = null;
			
			if($toolbar.data('src')) {
				$field = $toolbar.data('src');
			
			} else {
				$field = $toolbar.prev(':text, textarea');
			}
			
			if(null == $field)
				return;
			
			if(null == $field)
				return;
			
			if($field.is(':text, textarea')) {
				$field.focus().insertAtCursor('{literal}{{{/literal}' + token + '{literal}}}{/literal}');
				
			} else if($field.is('.ace_editor')) {
				var evt = new jQuery.Event('cerb.insertAtCursor');
				evt.content = '{literal}{{{/literal}' + token + '{literal}}}{/literal}';
				$field.trigger(evt);
			}
		}
	});
	
	$toolbar.find('button.tester').click(function(e) {
		var divTester = $toolbar.find('div.tester').first();
		
		var $field = null;
		
		if($toolbar.data('src')) {
			$field = $toolbar.data('src');
		} else {
			$field = $toolbar.prev(':text, textarea');
		}
		
		if(null == $field)
			return;
		
		if($field.is('.ace_editor')) {
			var $field = $field.prev('textarea, :text');
		}
		
		var regexpName = /^(.*?)\[(.*?)\]$/;
		var hits = regexpName.exec($field.attr('name'));
		
		if(null == hits || hits.length < 3)
			return;
		
		var strNamespace = hits[1];
		var strName = hits[2];
		
		genericAjaxPost($div.closest('form'), divTester, 'c=profiles&a=handleSectionAction&section=profile_widget&action=testWidgetTemplate&template_key=' + encodeURIComponent(strName));
	});
	
	$placeholder_menu_trigger
		.click(
			function(e) {
				$placeholder_menu.toggle();
			}
		)
		.bind('remove',
			function(e) {
				$placeholder_menu.remove();
			}
		)
	;
});
</script>