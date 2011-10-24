{extends file="fossil:forums/base"}
{block name=content}
<div class="forums_view">
{foreach $categories|array_keys as $category}
<div class="forum_category" id="forum_category_{$category->id|default:'none'}">
    {display source=$categories.$category}
</div>
{/foreach}
</div>
{/block}