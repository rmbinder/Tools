<p>{$l10n->get('PLG_BLSV_EXPORT_DESC')}</p>
<p>{$l10n->get('PLG_BLSV_EXPORT_DESC2')}</p>

<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {include 'sys-template-parts/form.radio.tpl' data=$elements['export_mode']}
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_export']}
</form>
