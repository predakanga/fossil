{extends file="fossil:forums/base"}
{block name=content}
<div class="forum_view" id="forum_view_{$forum->id}">
    {paginate source=$forum field="topics" header="fossil:forums/row_topic_header" footer="fossil:forums/row_topic_footer"}
    {link action="newTopic" fid=$forum->id}New topic{/link}
</div>
{/block}