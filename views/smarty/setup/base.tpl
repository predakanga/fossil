{extends file="base.tpl"}
{block name=assignations}
{assign var="title" value="Welcome" scope=global}
{assign var="title_verbose" value="Welcome to Fossil" scope=global}{/block}
{block name=content_layout}
<br />
<div class="box-16">
    <div class="box no-margin">
        {block name=content}Content goes here{/block}
    </div>
</div>
{/block}