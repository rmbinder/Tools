<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    {$l10n->get('PLG_TOOLS_ENABLE_SUBPLUGINS')}

    {foreach $existingPlugins as $existingPlugin}
        {include 'sys-template-parts/form.checkbox.tpl' data=$elements[{$existingPlugin.name}]} 
    {/foreach}
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_options']} 

    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
