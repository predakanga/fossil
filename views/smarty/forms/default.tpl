<div class="box-6">
{if $action}
    <form action="{$action}" method="{$method}">
{else}
    <form method="{$method}">
{/if}
        <input type="hidden" name="form_id" value="{$form_id}" />
{foreach $fields as $field}
        <label for="{$field.name}">{$field.label}:</label>
{if $field.type == "select"}
        <select name="{$field.name}">
{foreach $field.options as $opt}
{if $field.value && $field.value == $opt.value}
            <option value="{$opt.value}" selected>{$opt.label}</option>
{else}
            <option value="{$opt.value}">{$opt.label}</option>
{/if}
{/foreach}
        </select><br />
{else}
{if $field.value}
        <input type="{$field.type}" name="{$field.name}" value="{$field.value}">
{else}
        <input type="{$field.type}" name="{$field.name}">
{/if}</input>
{/if}
{/foreach}
        <input type="submit" value="Submit"></input>
    </form>
</div>