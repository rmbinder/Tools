<div class="table-responsive">
    <table id="adm_role_permissions_table" class="table table-hover" width="100%" style="width: 100%;">
        <thead>
            <tr>
                <th>{$l10n->get('SYS_CATEGORY')}</th>
                <th>{$l10n->get('SYS_GROUPS_ROLES')}</th>
                <th>{$l10n->get('SYS_DESCRIPTION')}</th>
                <th>{$l10n->get('SYS_CONTRIBUTION')}</th>
                <th>{$members}</th>
                <th>{$leader}</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            {foreach $list as $row}
                <tr id="role_{$row.roleUUID}">
                    <td>{$row.category}</td>
                    <td><a href="{$row.roleUrl}">{$row.role}</a></td>
                    <td>{$row.roleDescription}</td>
                    <td>{$row.roleContribution}</td>
                    <td>{$row.numMember}</td>
                    <td>{$row.numLeader}</td>
                    <td class="text-end">
                        {foreach $row.actions as $actionItem}
                            <a {if isset($actionItem.dataHref)} class="admidio-icon-link admidio-messagebox" href="javascript:void(0);"
                                data-buttons="yes-no" data-message="{$actionItem.dataMessage}" data-href="{$actionItem.dataHref}"
                                    {else} class="admidio-icon-link" href="{$actionItem.url}"{/if}>
                                <i class="{$actionItem.icon}" data-bs-toggle="tooltip" title="{$actionItem.tooltip}"></i></a>
                        {/foreach}
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>
