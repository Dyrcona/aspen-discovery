{strip}
	<div class="row" id="shareItSection">
		<div class="col-tn-12">
			<h2>{translate text="In %1%" 1=$interLibraryLoanName isPublicFacing=true}</h2>
			{translate text="Request items from other %1% libraries to be delivered to your local library for pickup." 1=$interLibraryLoanName isPublicFacing=true}
		</div>
	</div>

	{if !empty($shareItResults)}
		<div class="row" id="shareItSearchResultsSection">
			<div class="col-tn-12">

				<div class="striped">
					{foreach from=$shareItResults item=shareItResult}
						<div class="result">
							<div class="resultItemLine1">
								<a class="title" href='{$shareItResult.link}' rel="external" onclick="window.open(this.href, 'child'); return false">
									{$shareItResult.title}
								</a>
							</div>
							<div class="resultItemLine2">{if !empty($shareItResult.author)}by {$shareItResult.author} {/if}{if !empty($shareItResult.pubDate)}Published {$shareItResult.pubDate}{/if}</div>
						</div>
					{/foreach}
				</div>

			</div>
		</div>
	{/if}

	<div class="row" id="shareItLinkSection">
		<div class="col-tn-12">
			<br>
			<button class="btn btn-sm btn-info pull-right" onclick="window.open('{$shareItLink}', 'child'); return false">{translate text="See more results in %1%" 1=$interLibraryLoanName isPublicFacing=true}</button>
		</div>
	</div>

	<style>
		{literal}
		#shareItSection,#shareItSearchResultsSection {
			padding-top: 15px;
		}
		#shareItLinkSection {
			padding-bottom: 15px;
		}
		{/literal}
	</style>
{/strip}
