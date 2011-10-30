{extends file="setup/base.tpl"}
{block name=toc}
<ul class="icon-list">
    <li class="check">{link}Introduction{/link}</li>
    <li class="check">{link action="checkCompatibility"}Check compatibility{/link}</li>
    <li class="check">{link action="selectDrivers"}Select drivers{/link}</li>
    <li class="arrow">Configure drivers</li>
    <li class="bullet">Select plugins (optional)</li>
    <li class="bullet">Run tests (optional)</li>
    <li class="bullet">Start coding</li>
</ul>
{/block}
{block name=content}
<h2>Configure drivers</h2>
<p>Configure drivers here.</p>

<div class="box-6">
{multiform}
Database config:<br />
{if $dbForm}
{form name=$dbForm}
{else}
<p>No configuration needed.</p>
{/if}
<br />
Cache config:<br />
{if $cacheForm}
{form name=$cacheForm}
{else}
<p>No configuration needed.</p>
{/if}
<br />
Renderer config:<br />
{if $rendererForm}
{form name=$rendererForm}
{else}
<p>No configuration needed.</p>
{/if}
{/multiform}
</div>
<br /><br />
{/block}