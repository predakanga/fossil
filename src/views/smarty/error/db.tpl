{extends file="fossil:base"}
{block name=assignations}
{assign var="title" value="Error" scope=global}{/block}
{block name=content_layout}
<br />
<div class="box-16-expand">
    <div class="box">
        A database error occured.
{if $me->isDev()}
    <br />
    Error: {$e->getMessage()}<br />
    <br />
    Details:
    {$query|var_dump}
{/if}
    </div>
</div>
{/block}