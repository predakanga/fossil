<tr class="forum_topic" id="forum_topic_{$item->id}">
    <td class="topic_info">{link action="viewTopic" id=$item->id}{$item->name}{/link}</td>
    <td class="post_count">{$item->posts|count}</td>
    <td class="view_count">{$item->viewCount}</td>
    <td class="author">{link controller="user" action="view" id={$item->author->id}}{$item->author->name}{/link}</td>
    <td class="latest_post">{if $item->latestPost}{display source=$item->latestPost mode="forumSummary"}{else}--None--{/if}</td>
</tr>