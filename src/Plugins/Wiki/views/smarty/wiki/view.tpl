{extends file="fossil:wiki/base"}
{block name=content}
<h1>{$page->title}</h1><br />
<h3>(revision {$rev->revision} - {link action="edit" page=$page->id}edit{/link} | {link action="history" page=$page->id}history{/link})</h3><br />
<div class="box-12 push-1">
    {$rev->content|bbdecode}
</div>
{/block}