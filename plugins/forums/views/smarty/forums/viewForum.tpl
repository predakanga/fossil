{extends file="fossil:loggedIn"}
{block name=content}
<div class="forum_view" id="forum_view_{$forum->id}">
    {paginate source=$forum field="topics"}
    {link action="newTopic" fid=$forum->id}New topic{/link}
</div>
{/block}