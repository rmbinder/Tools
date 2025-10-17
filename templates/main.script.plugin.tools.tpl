<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
   
    <a class="admidio-icon-link float-end openPopup" href="javascript:void(0);" data-class="modal-lg" data-href="{$urlPopup}">
        <i class="bi bi-info-circle-fill admidio-info-icon"></i>
    </a>       
    <a class="admidio-icon-link float-end" href="{$urlSettings}">
        <i class="bi bi-gear-fill" title="{$l10n->get('SYS_SETTINGS')}"></i>
    </a>                 
    <br> 
    {$l10n->get('PLG_TOOLS_DESC')}
     
    {foreach $existingPlugins as $plugin }
        <a class="btn btn-primary" style= "text-align: center;width:75%" id="open_documentation" href="{$plugin}">
            </i>{$plugin@key}</a>      
            <br><br>    
    {/foreach}
        
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
