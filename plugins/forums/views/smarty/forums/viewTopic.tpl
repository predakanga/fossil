{extends file="fossil:loggedIn"}
{block name=content}
<div class="topic_view" id="topic_view_{$topic->id}">
    {paginate source=$topic field="posts"}
    {link action="newPost" tid=$topic->id}New post{/link}
</div>
{/block}