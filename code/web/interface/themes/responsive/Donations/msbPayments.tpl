{strip}
	<input type="hidden" name="patronId" value="{$userId}"/>
	<div id="msb-button-container{$userId}" class="center-block text-center">
		<button type="button" class="btn btn-lg btn-primary" onclick="return AspenDiscovery.Account.createMSBOrder('#donation{$userId}', 'donation');"><i class="fas fa-lock"></i> {translate text='Continue to Payment' isPublicFacing=true}</button>
	</div>
{/strip}
