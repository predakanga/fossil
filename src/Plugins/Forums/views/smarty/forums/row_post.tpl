<div class="forum_post" id="forum_post_{$item->id}">
    <div class="forum_post_header lightGreyGrad">
        <span class="forum_post_header_left">Posted {$now->diff($item->postedAt)|date_interval_format}</span>
        {link action="viewTopic" id=$item->topic->id fragment="forum_post_"|cat:$item->id}#{$index}{/link}
    </div>
    <div class="forum_post_main clearfix">
        <div class="forum_post_user">
            <span class="forum_post_username"><a href="#">{$item->author->name}</a></span>
            <img src="{$item->author->getAvatarURL(50)}" alt="{$item->author->name}" />
        </div>
        <div class="forum_post_content">
            {$item->content|nl2br|bbdecode}
        </div>
    </div>
</div>