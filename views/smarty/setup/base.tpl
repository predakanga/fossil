{extends file="base.tpl"}
{block name=assignations}
{assign var="title" value="Welcome" scope=global}
{assign var="title_verbose" value="Welcome to Fossil" scope=global}{/block}
{block name=content_layout}
<br />
<div class="box-16-expand">
    <div class="box-4-expand-right">
        <div class="box">
            <h3 class="no-margin">Getting Started</h3>
            {block name=toc}
            <ul class="icon-list">
                <li class="arrow">Introduction</li>
                <li class="bullet">Check compatibility</li>
                <li class="bullet">Select drivers</li>
                <li class="bullet">Configure drivers</li>
                <li class="bullet">Select plugins (optional)</li>
                <li class="bullet">Run tests (optional)</li>
                <li class="bullet">Start coding</li>
            </ul>
            {/block}
        </div>
    </div>
    <div class="box-12-expand-left">
        <div class="box no-margin cf">
            {block name=content}Content goes here{/block}
        </div>
    </div>
</div>
{/block}