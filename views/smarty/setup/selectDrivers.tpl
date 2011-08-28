{extends file="setup/base.tpl"}
{block name=toc}
<ul class="icon-list">
    <li class="check">{link}Introduction{/link}</li>
    <li class="check">{link action="checkCompatibility"}Check compatibility{/link}</li>
    <li class="arrow">Select drivers</li>
    <li class="bullet">Configure drivers</li>
    <li class="bullet">Select plugins (optional)</li>
    <li class="bullet">Run tests (optional)</li>
    <li class="bullet">Start coding</li>
</ul>
{/block}
{block name=content}
<h2>Select drivers</h2>
<p>Select drivers here.</p>

{form name="DriverSelection"}
<br /><br />
{/block}