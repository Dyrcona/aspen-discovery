{strip}
	<div id="page-content" class="content">
		<div id="main-content">
			<div class="resulthead"><h3>{translate text='Reset My PIN'}</h3></div>
			<div class="page">
				{if !$result.success && $result.error}
					<div class="alert alert-danger">{$result.error}</div>
					<div>
						<a class="btn btn-primary" role="button" href="/MyAccount/EmailResetPin">Try Again</a>
					</div>
				{elseif $result.message}
					<div class="alert alert-success">{$result.message}</div>
					<p>
						<a class="btn btn-primary" role="button" href="/MyAccount/Login">{translate text='Login'}</a>
					</p>
				{else}
					<p class="alert alert-success">
						A email has been sent to the email address on the circulation system for your account containing a link to reset your PIN.
					</p>
					<p class="alert alert-warning">
						If you do not receive an email within a few minutes, please check any spam folder your email service may have.   If you do not receive any email, please contact your library to have them reset your pin.
					</p>
					<p>
						<a class="btn btn-primary" role="button" href="/MyAccount/Login">{translate text='Login'}</a>
					</p>
				{/if}
			</div>
		</div>
	</div>
{/strip}