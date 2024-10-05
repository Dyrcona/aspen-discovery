{strip}
	<div id="main-content" class="col-md-12">
		<div class="doNotPrint">
			<h1>School Overdue Report</h1>
			{if isset($errors)}
				{foreach from=$errors item=error}
					<div class="error">{$error}</div>
				{/foreach}
			{/if}
			<form class="form form-inline">

				{html_options name=location options=$locationLookupList selected=$selectedLocation class="form-control input-sm"}

				<select name="showOverdueOnly" id="showOverdueOnly" class="form-control input-sm">
					<option value="overdue" {if $showOverdueOnly == "overdue"}selected="selected"{/if}>Overdue Items</option>
					<option value="checkedOut" {if $showOverdueOnly == "checkedOut"}selected="selected"{/if}>All Checked Out</option>
					<option value="fees" {if $showOverdueOnly == "fees"}selected="selected"{/if}>Fees</option>
				</select>
				&nbsp;
				<input type="submit" name="showData" value="Show Data" class="btn btn-sm btn-primary"/>
				&nbsp;
				<input type="button" name="printSlips" value="Print Slips" class="btn btn-sm btn-primary" onclick="{literal} var x = document.querySelectorAll('.overdueSlipContainer'); var i; for (i = 0; i < x.length; i++) { x[i].style.pageBreakBefore = 'auto'; } window.print(); {/literal}" />
				&nbsp;
				<input type="button" name="printPages" value="Print Pages" class="btn btn-sm btn-primary" onclick="{literal} var x = document.querySelectorAll('.overdueSlipContainer'); var i; for (i = 0; i < x.length; i++) { x[i].style.pageBreakBefore = 'always'; } window.print(); {/literal}" />
				&nbsp;
				<input type="submit" name="download" value="Download CSV" class="btn btn-sm btn-info"/>
				&nbsp;
			</form>
			{if !empty($reportData)}
				<br/>
				<p>
					There are a total of <strong>{$reportData|@count}</strong> {if $showOverdueOnly == "overdue"}overdue items{elseif $showOverdueOnly == "checkedOut"}items out{elseif $showOverdueOnly == "fees"}fees{/if}.
				</p>
		</div>
{literal}
<style>
	.overdueSlip.container {
		border-top: 1px dashed #ccc !important;
		page-break-inside: avoid !important;
		width: 7in !important;
		padding: 0 !important;
		outline: 1px solid #0f0;
	}
	.overdueSlip.container .row {
		margin: 0 !important;
		padding: 0 0 0 0 !important;
	}
	.patronHeader {
		font-weight: bold;
	}
	.patronHeader div {
		white-space: nowrap;
		overflow: hidden;
	}
	.overdueRecordTableMessage {
	}
	.overdueRecordContent.card {
	}
	.card-body {
		outline: #ff00ff 1px solid;
	}
	.BOOK_COVER {
		max-width: .75in;
	}
	.BOOK_COVER img {
		max-width: .7in;
	}
	.overdueRecordContentDetails {
		hyphens: auto;
	}
	.TITLE {
		flex-wrap: wrap !important;
	}
	table#studentReportTable {
		width: 8in;
		margin-left: 0;
		margin-right: auto;
		font: inherit;
		border: 0;
	}
	table#studentReportTable .hideit {
		display: none;
	}
	table#studentReportTable thead {
		display: table !important;
	}
	table#studentReportTable tbody tr td {
		border: 0;
	}

	@media print {
		@page {
			margin: .5in !important; /* Set the desired margin size */
		}


		div.breadcrumbs {
			display: none !important;
		}
		div.doNotPrint {
			display: none !important;
		}
		div#page-header {
			display: none !important;
		}
		div#system-message-header {
			display: none !important;
		}
		div#footer-container {
			display: none !important;
		}
		table#studentReportTable .displayScreen {
			display: none !important;
		}
		table#studentReportTable thead {
			display: none !important;
		}


		/* Chromium + Safari */
		/* fix for "Print Pages" */
		@supports (not (-moz-appearance: none)) {
			tr.overdueSlipContainer {
				display: block;
			}
		}
	}
</style>
{/literal}

		<table id="studentReportTable">
			<thead>
				<tr>
					<th class="filter-select filter-onlyAvail">Grade</th>
					<th class="filter-select filter-onlyAvail">Homeroom</th>
					<th class="sorter-false">Student ID</th>
					<th class="filter">Student Name</th>
					<th class="sorter-false">Notice</th>
				<tr>
			</thead>
			<tbody>
{assign var=previousPatron value=0}
{foreach from=$reportData item=dataRow name=overdueData}
	{if $dataRow.P_BARCODE != $previousPatron}
{if $smarty.foreach.overdueData.index > 0}</div></td></tr>{/if}
				<tr class="overdueSlipContainer">
					<td class="hideit">{$dataRow.GRD_LVL|replace:' student':''|replace:'MNPS School Librar':'0.0 MNPS School Librar'|replace:'MNPS Staff':'0.1 MNPS Staff'|replace:'Pre-K':'0.2 Pre-K'|replace:'Kindergar':'0.3 Kindergar'|replace:'First':'1 First'|replace:'Second':'2 Second'|replace:'Third':'3 Third'|replace:'Fourth':'4 Fourth'|replace:'Fifth':'5 Fifth'|replace:'Sixth':'6 Sixth'|replace:'Seventh':'7 Seventh'|replace:'Eighth':'8 Eighth'|replace:'Ninth':'9 Ninth'|replace:'Tenth':'10 Tenth'|replace:'Eleventh':'11 Eleventh'|replace:'Twelfth':'12 Twelfth'|regex_replace:'/^.*no LL delivery/':'13 no LL delivery'|replace:'MNPS 18+':'13 MNPS 18+'}</td>
					<td class="hideit">{$dataRow.HOME_ROOM|lower|capitalize:true}</td>
					<td class="hideit">{$dataRow.P_BARCODE}</td>
					<td class="hideit">{$dataRow.PATRON_NAME}</td>
					<td>
						<div class="overdueSlip container">
							<div class="patronHeader row">
								<div class="P_TYPE col-md-2">{$dataRow.GRD_LVL|replace:' student':''}</div>
								<div class="HOME_ROOM col-md-3">{$dataRow.HOME_ROOM|lower|capitalize:true}</div>
								<div class="PATRON_NAME col-md-5">{$dataRow.PATRON_NAME|upper}</div>
								<div class="P_BARCODE col-md-2">{$dataRow.P_BARCODE}</div>
							</div>
							<div class="overdueRecordTable row">
								<div class="overdueRecordTableMessage col-md-12">
									The items below are
									{if $showOverdueOnly == "overdue"}&nbsp;overdue
									{elseif $showOverdueOnly == "checkedOut"}&nbsp;checked out
									{elseif $showOverdueOnly == "fees"}&nbsp;billed{/if}
									.&nbsp;
									Please return them to your library. This notice was created {$reportDateTime}<br>
									Check your account online at https://school.library.nashville.org/<br>
									Read off your fees in October and February! Learn more at https://limitlesslibraries.org/programs
								</div>
							</div>
							<div class="overdueRecord row d-flex flex-wrap">
		{assign var=previousPatron value=$dataRow.P_BARCODE}
	{/if}
								<div class="overdueRecordContent card col-md-4 mb-4">
									<div class="overdueRecordContent card-body">
										<div class="row">
											<div class="BOOK_COVER col-md-4"><img class="img-fluid" src="{$dataRow.coverUrl}"></div>
											<div class="overdueRecordContentDetails col-md-8">
{*												<div class="SYSTEM">{$dataRow.SYSTEM|replace:"1":"NPL"|replace:"2":"MNPS"}</div>*}
{*												<div class="ITEM_ID">{$dataRow.ITEM}</div>*}
{*												<div class="CALL_NUMBER">{$dataRow.CALL_NUMBER}</div>*}
												<div class="TITLE">{$dataRow.TITLE|regex_replace:"/ *\/ *$/":""}</div>
												<div class="DUE_DATE">DUE: {$dataRow.DUE_DATE}</div>
												<div class="PRICE">{if $showOverdueOnly == "overdue" || $showOverdueOnly == "checkedOut"}PRICE{elseif $showOverdueOnly == "fees"}OWED{/if}: {$dataRow.OWED|regex_replace:"/^ *0\.00$/":"10.00"}</div>
											</div>
										</div>
									</div>
								</div>
{/foreach}
					</td>
				</tr>
			</tbody>
		</table>
		<script type="text/javascript">
			{literal}
				$(document).ready(function(){
					$('#studentReportTable').tablesorter({
						widgets: ["filter"],
						widgetOptions: {
							filter_hideFilters : false,
							filter_ignoreCase: true
						}
					});
				});
			{/literal}
		</script>
	{/if}
	</div>
{/strip}
