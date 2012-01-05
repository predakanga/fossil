{extends file="fossil:wiki/base"}
{block name=content}
<h1>Wiki pages</h1><br />
<br />
<div class="box-12 push-1">
    <ul>
{paginate source=$pages template="fossil:wiki/row_wikipage"}
    </ul>
</div>
{/block}