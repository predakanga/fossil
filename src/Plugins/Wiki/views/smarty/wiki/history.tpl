{extends file="fossil:wiki/base"}
{block name=content}
<h1>{$page->title}'s history</h1><br />
<br />
<div class="box-12 push-1">
    <ul>
{foreach $page->revisions as $rev}
        <li>{link action="view" page=$page->id revision=$rev->revision}Revision {$rev->revision}{/link}, created at {$rev->authoredAt|date_format}, by {$rev->author->name}. Summary: {$rev->editSummary}</li>
{/foreach}
    </ul>
    {link action="delete" page=$page->id}Delete{/link} this page.<br />
</div>
{/block}