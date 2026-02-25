<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    <div class="admidio-form-required-notice"><span>{$l10n->get('SYS_REQUIRED_INPUT')}</span></div>
    
    {include 'sys-template-parts/form.select.tpl' data=$elements['selection_role']}
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['make_former']}
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_make_former']} 
    
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['remove_contact']}
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_remove_contact']} 
    
</form>
