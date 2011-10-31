<tr class="forum_subforum" id="forum_subforum_{$item->id}">
    <td class="forum_info">
        {link action="viewForum" id=$item->id}{$item->name}{/link}<br />
        {$item->description}
    </td>
    <td class="topic_count">{$item->topics|count}</td>
    <td class="post_count">{$item->getPostCount()}</td>
    <td class="latest_post">{if $item->latestPost}{display source=$item->latestPost mode="forumSummary"}{else}--None--{/if}</td>
</tr>