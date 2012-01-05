{extends file="fossil:wiki/base"}
{block name=content}
<h1>{$page->title}</h1><br />
<br />
<div class="box-12 push-1">
    Invalid revision - click {link action="history" page=$page->id}here{/link} to see all possible revisions
</div>
{/block}