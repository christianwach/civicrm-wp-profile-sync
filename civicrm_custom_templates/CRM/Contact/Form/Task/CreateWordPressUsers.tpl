{if $rows}
  <div class="crm-submit-buttons element-right">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

  <div class="spacer"></div>

  <div>
    <br />
    <table>
      <tr class="columnheader">
        <td>{$tableID}</td>
        <td>{$tableType}</td>
        <td>{$tableDisplayName}</td>
        <td>{$tableEmail}</t>
        <td>{$tableUser}</t>
      </tr>
    {foreach from=$rows item=row}
      <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.id}</td>
        <td>{$row.contact_type}</td>
        <td>{$row.display_name}</td>
        <td>{$row.email}</td>
        <td>{$row.has_user}</td>
      </tr>
    {/foreach}
    </table>
  </div>

  <div class="form-item element-right">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

{else}

  <div class="messages status no-popup">
  <div class="icon inform-icon"></div>
    {$notFound}
  </div>

{/if}
