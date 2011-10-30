{extends file="setup/base.tpl"}
{block name=toc}
<ul class="icon-list">
    <li class="check">{link}Introduction{/link}</li>
    <li class="arrow">Check compatibility</li>
    <li class="bullet">Select drivers</li>
    <li class="bullet">Configure drivers</li>
    <li class="bullet">Select plugins (optional)</li>
    <li class="bullet">Run tests (optional)</li>
    <li class="bullet">Start coding</li>
</ul>
{/block}
{block name=content}
<h2>Checking Compatibility</h2>
<p>Fossil has various dependencies, some required, some optional.<br />
This page is intended to inform you about any missing or incorrect dependencies.</p>

<h3>Required Dependencies</h3>
<table>
    <thead>
        <tr><th></th><th>Name</th><th>Version</th><th>Type</th><th>Website</th></tr>
    </thead>
    <tbody>
        {foreach $Required as $name => $data}
        <tr>
            <td>
                {if $data.Result}<img src="static/images/icon-check.png" alt="OK" />
                {else}<img src="static/images/icon-cross.png" alt="Not OK" />{/if}
            </td>
            <td>{$name}</td>
            <td>{$data.Version}</td>
            <td>{$data.Type}</td>
            <td>{$data.URL}</td>
        </tr>
        {/foreach}
    </tbody>
</table>

<h3>Optional Dependencies</h3>
<table>
    <thead>
        <tr><th></th><th>Name</th><th>Version</th><th>Type</th><th>Website</th></tr>
    </thead>
    <tbody>
        {foreach $Optional as $name => $data}
        <tr>
            <td>
                {if $data.Result}<img src="static/images/icon-check.png" alt="OK" />
                {else}<img src="static/images/icon-cross.png" alt="Not OK" />{/if}
            </td>
            <td>{$name}</td>
            <td>{$data.Version}</td>
            <td>{$data.Type}</td>
            <td>{$data.URL}</td>
        </tr>
        {/foreach}
    </tbody>
</table>

{if $allOK}
<div class="box-2">
    {link cssClass="boxLink" action="selectDrivers"}Next &gt;{/link}
</div>
{else}
Please install/upgrade all required dependencies to continue.
{/if}
<br /><br />
{/block}