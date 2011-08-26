{extends file="setup/base.tpl"}
{block name=content}
<h2>Your new PHP Framework</h2>
<p>
Fossil is a full-featured framework, supporting everything from the mundane to advanced features.<br />
Designed for Rapid Application Design and Development, Fossil provides everything you need to get started, and for your more esoteric needs, the community provides their own solutions at the Dig Site.
</p>
<h2>Features</h2>
<ul>
    <li>Easy to install, easy to use</li>
    <li>First-class database support (MySQL, PostgreSQL, SQLite)</li>
    <li>Multiple template engines - use whatever you're comfortable with</li>
    <li>Extensive plugin support</li>
    <li>Integrate with other services, with REST, JSON and SOAP support</li>
    <li>Keep your site blazing fast, with extensive caching and optimizations</li>
</ul>
<br />
<h2>Getting Started</h2>
<ul>
    <li>Check compatibility</li>
    <li>Select drivers</li>
    <li>Configure drivers</li>
    <li>Select plugins (optional)</li>
    <li>Run tests (optional)</li>
    <li>Start coding</li>
</ul>
<div class="box-2">
    <a class="boxLink" href="?controller=setup&action=checkCompatibility">Start &gt;</a>
</div>
<br /><br />

{/block}