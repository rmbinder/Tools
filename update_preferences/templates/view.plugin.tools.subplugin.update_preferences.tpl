<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {if (isset($elements['btn_save']))}
        {include 'sys-template-parts/form.description.tpl' data=$elements['dsc_prefs_found']}   
        {include 'sys-template-parts/form.description.tpl' data=$elements['dsc_miss_txt']}             
            
        {include 'sys-template-parts/form.button.tpl' data=$elements['btn_save']} 
    {else}
        {include 'sys-template-parts/form.description.tpl' data=$elements['dsc_nothing_found']}             
    {/if}    
    
</form>
