<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    {if (isset($elements['btn_create']))}
        {include '../templates/form.custom-content.popover.plugin.tools.subplugin.remove_gender_language.tpl' data=$elements['cc_create'] popover="{$l10n->get('PLG_REMOVE_GENDER_LANGUAGE_MAX_FILES')}"}
        {include 'sys-template-parts/form.button.tpl' data=$elements['btn_create']}                         
    {/if} 
   
    {if (isset($elements['cc_save']))}
        {include '../templates/form.custom-content.popover.plugin.tools.subplugin.remove_gender_language.tpl' data=$elements['cc_save'] popover="{$l10n->get('PLG_REMOVE_GENDER_LANGUAGE_MAX_FILES')}"}
    {/if}    
    {if (isset($elements['cc_backup_restore']))}
        {include 'sys-template-parts/form.custom-content.tpl' data=$elements['cc_backup_restore']}   
    {/if}   
    {if (isset($elements['cc_obselete']))}
        {include 'sys-template-parts/form.custom-content.tpl' data=$elements['cc_obselete']}   
    {/if}   
        
    {if (isset($elements['btn_restore_or_delete']))}
        {include 'sys-template-parts/form.select.tpl' data=$elements['backup_file']}                   
        {include 'sys-template-parts/form.select.tpl' data=$elements['sct_restore_or_delete']}                      
        {include 'sys-template-parts/form.button.tpl' data=$elements['btn_restore_or_delete']}                      
    {/if}                                               
 
    {include '../templates/form.custom-content.popover.plugin.tools.subplugin.remove_gender_language.tpl' data=$elements['cc_replace'] popover="{$l10n->get('PLG_REMOVE_GENDER_LANGUAGE_INFO_CHANGE_TEXT')}"}  
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_replace']} 
    
</form>
