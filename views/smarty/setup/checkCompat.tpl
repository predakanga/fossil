{extends file="setup/base.tpl"}
{block name=toc}
<ul class="icon-list">
    <li class="check">Introduction</li>
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
        <tr><th></th><th>Name</th><th>Type</th><th>Website</th></tr>
    </thead>
    <tbody>
        <tr><td></td><td>Smarty</td><td>Template engine</td><td>http://www.smarty.com</td></tr>
    </tbody>
</table>

<h3>Optional Dependencies</h3>
<table>
    <thead>
        <tr><th></th><th>Name</th><th>Type</th><th>Website</th></tr>
    </thead>
    <tbody>
        <tr><td></td><td>PHPUnit</td><td>Testing</td><td>http://www.phpunit.de</td></tr>
    </tbody>
</table>


{/block}