{extends file="fossil:wiki/base"}
{block name=content}
<h1>{$page->title}</h1><br />
<br />
<div class="box-12 push-1">
    This page does not exist yet. Click {link action="edit" page=$page->id}here{/link} to create it.
</div>
{/block}