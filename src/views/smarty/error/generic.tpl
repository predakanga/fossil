{extends file="fossil:base"}
{block name=assignations}
{assign var="title" value="Error" scope=global}{/block}
{block name=content_layout}
<br />
<div class="box-16-expand">
    <div class="box">
        An error occured: {$e->getMessage()}
    </div>
</div>
{/block}