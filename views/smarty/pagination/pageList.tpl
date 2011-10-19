{$startIdx=max(1, $curPage-2)}
{$endIdx=min($curPage+2, $pageCount)}
{if $curPage > 1}
{link_page page=1}First &lt;&lt;{/link_page} |
{link_page page=$curPage-1}Prev &lt;{/link_page} |
{/if}
{for $page=$startIdx to $endIdx}
{if $page == $curPage}
{$page} |
{else}
{link_page page=$page}{$page}{/link_page} |
{/if}
{/for}
{if $curPage < $pageCount}
{link_page page=$curPage+1}Next &gt;{/link_page} |
{link_page page=$pageCount}Last &gt;&gt;{/link_page}
{/if}
