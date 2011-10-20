<div class="forum_topic" id="forum_topic_{$item->id}">
    Index: {$index}<br />
    Title: {$item->name}<br />
    Author: {$item->author->name}<br />
    Post count: {$item->posts|count}<br />
    Latest post ID: {$item->latestPost->id}<br />
    {link action="viewTopic" id=$item->id}View topic{/link}
{*    Post count: {$item->getPostCount()}<br />
    Last post: {display source=$item->latestPost}*}
</div>