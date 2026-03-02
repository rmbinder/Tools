<p>{$l10n->get('PLG_REMOVE_GENDER_LANGUAGE_EDIT')}</p>

<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    <p>{$replacementstext}</p>
    <p><strong>{$l10n->get('PLG_REMOVE_GENDER_LANGUAGE_EDIT_INFO')}</strong></p>
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_save_configurations']}
</form>
