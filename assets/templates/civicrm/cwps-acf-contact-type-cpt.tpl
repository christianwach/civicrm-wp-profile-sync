{* template block that contains the new fields *}
<table>
  <tr class="cwps_acf_block">
    <td class="label"><label for="cwps_acf_cpt">{$form.cwps_acf_cpt.label}</label></td>
    <td>{$form.cwps_acf_cpt.html}</td>
  </tr>
</table>

{* reposition the above blocks after #someOtherBlock *}
<script type="text/javascript">
  {literal}

  // jQuery will not move an item unless it is wrapped.
  cj('tr.cwps_acf_block').insertAfter('.crm-contact-type-form-block .crm-contact-type-form-block-parent_id');

  {/literal}
</script>
