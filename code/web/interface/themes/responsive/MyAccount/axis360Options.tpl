{strip}
	<div id="main-content">
		{if !empty($loggedIn)}
			{if !empty($profile->_web_note)}
				<div class="row">
					<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
				</div>
			{/if}
			{if !empty($accountMessages)}
				{include file='systemMessages.tpl' messages=$accountMessages}
			{/if}

			<h1>{translate text='Boundless Options' isPublicFacing=true}</h1>
			{if !empty($offline)}
				<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
			{else}
				{* MDN 7/26/2019 Do not allow access for linked users *}
				{*				{include file="MyAccount/switch-linked-user-form.tpl" label="View Account Settings for" actionPath="/MyAccount/Axis360Options"}*}

				{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
				<form action="" method="post">
					<input type="hidden" name="updateScope" value="overdrive">
					<div class="form-group propertyRow">
						<label for="axis360Email" class="control-label">{translate text='Boundless Hold email' isPublicFacing=true}</label>
						{if $edit == true}<input name="axis360Email" id="axis360Email" class="form-control" value='{$profile->axis360Email|escape}' size='50' maxlength='75'>{else}{$profile->axis360Email|escape}{/if}
					</div>
					<div class="form-group propertyRow">
						<label for="promptForAxis360Email" class="control-label">{translate text='Prompt for Boundless email' isPublicFacing=true}</label>&nbsp;
						{if $edit == true}
							<input type="checkbox" name="promptForAxis360Email" id="promptForAxis360Email" {if $profile->promptForAxis360Email==1}checked='checked'{/if} data-switch="">
						{else}
							{if $profile->promptForAxis360Email==0}{translate text="No" isPublicFacing=true}{else}{translate text="Yes" isPublicFacing=true}{/if}
						{/if}
					</div>
					{if empty($offline) && $edit == true}
						<div class="form-group propertyRow">
							<button type="submit" name="updateAxis360" class="btn btn-sm btn-primary">{translate text="Update Options" isPublicFacing=true}</button>
						</div>
					{/if}
					</form>

					<script type="text/javascript">
						{* Initiate any checkbox with a data attribute set to data-switch=""  as a bootstrap switch *}
						{literal}
						$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
						{/literal}
					</script>

					</div>
			{/if}
		{else}
			<div class="page">
				{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
			</div>
		{/if}
	</div>
{/strip}
