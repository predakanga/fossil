{extends file="fossil:loggedIn"}
{block name="assignations"}
{assign var="title" value="Wiki" scope=global}
{assign var="title_verbose" value="Fossil Wiki" scope=global}
{assign var="colStyle" value="one-col" scope=global}
{/block}
{block name=content_layout}
		<div class="box-16-expand">
			<ul class="menu-horizontal no-margin">
				<li class="current"><a>Home</a></li>
				<li><a>Users</a></li>
				<li><a>Settings</a></li>
				<li><a>About</a></li>
			</ul>
			<div class="box-16-contract">
				<div class="box cf">
					{block name=content}Wiki content goes here{/block}
				</div>
			</div>
		</div>
{/block}
{block name="css" append}
	<link rel="stylesheet" href="static/wiki.css" type="text/css" charset="utf-8">
{/block}