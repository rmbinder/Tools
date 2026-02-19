<p>{$l10n->get('PLG_BLSV_EXPORT_EDIT')}</p>

<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    <p>{$configtext}</p>
    <p><strong>{$l10n->get('PLG_BLSV_EXPORT_EDIT_INFO')}</strong></p>
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_save_configurations']}
</form>
