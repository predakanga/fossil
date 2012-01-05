{extends file="fossil:wiki/base"}
{block name=content}
<h1>{$page->title}</h1><br />
<br />
<div class="box-12 push-1">
    Click {link action="delete" page=$page->id confirm="yes"}here{/link} to confirm that you want to delete this page
</div>
{/block}