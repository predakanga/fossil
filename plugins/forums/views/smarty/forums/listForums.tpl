{extends file="fossil:forums/base"}
{block name=content_layout}
<div class="forums_view">
{foreach $categories as $category}
<div class="forum_category box" id="forum_category_{$category.info.id}">
    <div class="box_header medGreyGrad">
        <h3>{$category.info.name}</h3>
    </div>
    <div class="box_body">
        {display source=$category.forums}
    </div>
</div>
{/foreach}
</div>
{/block}