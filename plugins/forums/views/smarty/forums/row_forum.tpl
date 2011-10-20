<div class="forum_subforum" id="forum_subforum_{$item->id}">
    Index: {$index}<br />
    Subcategory: {$item->name}<br />
    Topic count: {$item->topics|count}<br />
    {link action="viewForum" id=$item->id}View forum{/link}
{*    Post count: {$item->getPostCount()}<br />
    Last post: {display source=$item->latestPost}*}
</div>