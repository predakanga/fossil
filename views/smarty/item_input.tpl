{extends file="base.tpl"}
{block name=content}
<form method="POST">
<input type="hidden" name="controller" value="index" />
<input type="hidden" name="action" value="store" />
<input type="hidden" name="form_id" value="ItemStorage" />
<input type="text" name="item"></input>
<input type="submit" value="Save"></input>
</form>
{/block}