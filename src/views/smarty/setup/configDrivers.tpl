{extends file="setup/base.tpl"}
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