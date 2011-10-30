{extends file="setup/base.tpl"}
{block name=toc}
<ul class="icon-list">
    <li class="check">{link}Introduction{/link}</li>
    <li class="check">{link action="checkCompatibility"}Check compatibility{/link}</li>
    <li class="check">{link action="selectDrivers"}Select drivers{/link}</li>
    <li class="check">Configure drivers</li>
    <li class="check">Select plugins (optional)</li>
    <li class="check">Run tests (optional)</li>
    <li class="arrow">Start coding</li>
</ul>
{/block}
{block name=content}
<h2>You're ready to start coding</h2>
<p>
Go for it.
</p>
<div class="box-2">
    {link controller="index" cssClass="boxLink"}Start &gt;{/link}
</div>
<br /><br />

{/block}