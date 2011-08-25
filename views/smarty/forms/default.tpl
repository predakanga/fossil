{if $action}
<form action="{$action}" method="{$method}">
{else}
<form method="{$method}">
{/if}
    <input type="hidden" name="form_id" value="{$form_id}" />
{foreach $fields as $field}
{if $field.value}
    <input type="{$field.type}" name="{$field.name}" value="{$field.value}"></input>
{else}
    <input type="{$field.type}" name="{$field.name}"></input>
{/if}
{/foreach}
    <input type="submit" value="Submit"></input>
</form>