<div class="forum_post" id="forum_post_{$item->id}">
    Index: {$index}<br />
    Author: {$item->author->name}<br />
    Posted at: {$item->postedAt->format("Y-m-d H:i:s")}<br />
    Content: {$item->content|nl2br|bbdecode}<br />
</div>