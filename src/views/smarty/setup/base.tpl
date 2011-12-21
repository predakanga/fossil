{extends file="fossil:base"}
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
{foreach $steps as $stepNo=>$step}
{if $stepNo < $currentStep}
                <li class="check">{link action=$step.action}{$step.desc}{/link}</li>
{elseif $stepNo == $currentStep}
                <li class="arrow">{$step.desc}</li>
{else}
                <li class="bullet">{$step.desc}</li>
{/if}
{/foreach}
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