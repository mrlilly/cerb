{if !empty($error)}
<div class="error-box">
	<h1>{'common.error'|devblocks_translate|capitalize}</h1>
	<p>{ChSignInPage::getErrorMessage($error)}</p>
</div>
{/if}

<form action="{devblocks_url}c=login&a=router{/devblocks_url}" method="post" id="loginForm">
<fieldset>
	<legend>{'header.signon'|devblocks_translate|capitalize}</legend>
	
	<b>{'common.email'|devblocks_translate|capitalize}:</b>
	<br>
	
	<input type="text" name="email" size="45" value="{$email}">

	<div style="margin-left: 10px;">
		<label><input type="checkbox" name="remember_me" value="1" {if !empty($remember_me)}checked="checked"{/if}> Remember me</label>
	</div>
	
	<p>
		<button type="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.continue'|devblocks_translate|capitalize}</button>
	</p>
</fieldset>
</form>

<script type="text/javascript">
$(function() {
	$('#loginForm input[name=email]').focus().select();
});
</script>