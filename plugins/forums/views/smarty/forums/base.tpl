{extends file="fossil:loggedIn"}
{block name="assignations"}
{assign var="title" value="Forums" scope=global}
{assign var="title_verbose" value="Fossil Forums" scope=global}
{/block}
{block name="css" append}
	<link rel="stylesheet" href="static/forums.css" type="text/css" charset="utf-8">
{/block}