{* template block that contains the new fields *}
<table>
  <tr class="cwps_acf_enable_cpt">
    <td class="label"></td>
    <td>
    	{$form.cwps_acf_enable_cpt.html} <label for="cwps_acf_enable_cpt">{$form.cwps_acf_enable_cpt.label}</label>
 	   <br>
    	<span class="description">{$cwps_acf_enable_cpt_help}</span>
    </td>
  </tr>
</table>

{* reposition the above block after #someOtherBlock *}
<script type="text/javascript">
  {literal}

  // jQuery will not move an item unless it is wrapped.
  cj('tr.cwps_acf_enable_cpt').insertAfter('.crm--form-block-show_events');

  {/literal}
</script>
