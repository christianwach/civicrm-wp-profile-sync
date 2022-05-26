{* template block that contains the new fields *}
<table>
  <tr class="geo_mashup">
    <td class="label"><label for="geo_mashup">{$form.geo_mashup.label}</label></td>
    <td>{$form.geo_mashup.html} <span class="description">{$geo_mashup_help}</span></td>
  </tr>
  <tr class="geo_mashup_metabox">
    <td class="label"><label for="geo_mashup_metabox">{$form.geo_mashup_metabox.label}</label></td>
    <td>{$form.geo_mashup_metabox.html} <span class="description">{$geo_mashup_metabox_help}</span></td>
  </tr>
</table>

{* reposition the above block after #someOtherBlock *}
<script type="text/javascript">
  {literal}

  // Define vars.
  var geo_mashup_post_types = {/literal}{$geo_mashup_post_types}{literal},
      cwps_select = cj('#cwps_acf_cpt'),
      geo_mashup = cj('tr.geo_mashup'),
      geo_mashup_metabox = cj('tr.geo_mashup_metabox'),
      geo_mashup_checkbox = cj('#geo_mashup');

  // Move items into place below Post Type selector.
  geo_mashup.insertAfter('.crm-contact-type-form-block tr.cwps_acf_block');
  geo_mashup_metabox.insertAfter('.crm-contact-type-form-block tr.geo_mashup');

  /**
   * Toggle visibility of "enabled" checkbox table row.
   *
   * @since 0.5.8
   *
   * - Shows when a Post Type is selected.
   * - Hides if no Post Type selected.
   */
  function geo_mashup_toggle_cpt() {
    var post_type = cwps_select.val(),
        is_enabled = 0;

    // Check if the Post Type is enabled in Geo Mashup.
    if ( post_type && cj.inArray( post_type, geo_mashup_post_types ) !== -1 ) {
      is_enabled = 1;
    }

    // Do toggle.
    if ( is_enabled ) {
      geo_mashup.show();
      geo_mashup_metabox_toggle_cpt();
    } else {
      geo_mashup.hide();
      geo_mashup_metabox.hide();
    }
  }

  /**
   * Toggle visibility of "metabox" checkbox table row.
   *
   * @since 0.5.8
   *
   * - Shows when a Geo Mashup is enabled.
   * - Hides if Geo Mashup is disabled.
   */
  function geo_mashup_metabox_toggle_cpt() {
    if ( geo_mashup_checkbox.is( ':checked' ) ) {
      geo_mashup_metabox.show();
    } else {
      geo_mashup_metabox.hide();
    }
  }

  // Check immediately.
  geo_mashup_toggle_cpt();
  geo_mashup_metabox_toggle_cpt();

  /**
   * Checks when different Post Type option is chosen.
   *
   * @since 0.5.8
   *
   * @param {Object} event The jQuery event object.
   */
  cwps_select.on( 'change', function( event ) {
    geo_mashup_toggle_cpt();
  });

  /**
   * Checks when "enabled" checkbox is clicked.
   *
   * @since 0.5.8
   *
   * @param {Object} event The jQuery event object.
   */
  geo_mashup_checkbox.on( 'click', function( event ) {
    geo_mashup_metabox_toggle_cpt();
  });

  {/literal}
</script>
