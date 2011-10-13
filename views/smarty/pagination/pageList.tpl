{$startIdx=max(1, $curPage-2)}
{$endIdx=min($curPage+2, $pageCount)}
{if $curPage != 1}
{link_page page=1}First <<{/link_page} |
{link_page page=$curPage-1}Prev <{/link_page} |
{/if}
{for $page=$startIdx to $endIdx}
{if $page == $curPage}
{$page} |
{else}
{link_page page=$page}{$page}{/link_page} |
{/if}
{/for}
{if $curPage != $pageCount}
{link_page page=$curPage+1}Next >{/link_page} |
{link_page page=$pageCount}Last >>{/link_page}
{/if}
