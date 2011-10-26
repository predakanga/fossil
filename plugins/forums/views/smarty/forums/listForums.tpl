{extends file="fossil:forums/base"}
{block name=content_layout}
<div class="forums_view">
{foreach $categories as $category}
<div class="forum_category box" id="forum_category_{$category.info.id}">
    <div class="box_header medGreyGrad">
        <h3>{$category.info.name}</h3>
    </div>
    <div class="box_body">
        <table>
            <thead>
                <tr class="lightGreyGrad"><th>Forum</th><th>Topics</th><th>Posts</th><th>Latest Post</th></tr>
            </thead>
            <tbody>
                {display source=$category.forums}
            </tbody>
        </table>
    </div>
</div>
{/foreach}
</div>
{/block}