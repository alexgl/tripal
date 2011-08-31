
<?php
 //dpm(get_defined_vars(), 'variables');
 //dpm($element, 'element');
 $entity = $element['#element'];
?>

<!-- Authoring information -->
<?php
$creation_user = user_load($entity->created_by);
$modification_user = user_load($entity->last_modified_by);
?>

<table id="chado-base-table-base" class="chado-base-table tripal-table tripal-table-vert">
    <tr class="chado-base-table-odd-row tripal-table-odd-row">
      <th width='33%'>Genus</th>
      <td><?php print $entity->chado->genus ?></td>
    </tr>
    <tr class="chado-base-table-odd-row tripal-table-even-row">
      <th>Species</th>
      <td><?php print $entity->chado->species ?></td>
    </tr>       	                                
    <tr class="chado-base-table-odd-row tripal-table-even-row">
      <th>Abbreviation</th>
      <td><?php print $entity->chado->abbreviation ?></td>
    </tr>
    <tr class="chado-base-table-odd-row tripal-table-even-row">
      <th>Common Name</th>
      <td><?php print $entity->chado->common_name ?></td>
    </tr>
    <tr class="chado-base-table-odd-row tripal-table-even-row">
      <th>Breif Description</th>
      <td><?php print $entity->chado->comment ?></td>    
    </tr>
</table>

<br>
<h2>Author Information</h2>
 <table id="chado-authoring-table-base" class="chado-authoring-table tripal-table tripal-table-vert">
    <tr class="chado-authoring-table-odd-row tripal-table-even-row">
      <th width='33%'>Created By</th>
      <td><?php print $creation_user->name ?></td>
    </tr>
    <tr class="chado-authoring-table-odd-row tripal-table-odd-row">
      <th>Creation Date</th>
      <td><?php print date('l, F j, o',$entity->created_on) ?></td>
    </tr>
    <tr class="chado-authoring-table-odd-row tripal-table-even-row">
      <th>Last Modified By</th>
      <td><?php print $modification_user->name ?></td>
    </tr>       	                                
 </table>

<br>
<h2>Additional Information</h2>
<?php 
  // additional content added though field UI (manage fields on structure page)
  foreach ($element as $key => $info) {
    if (!preg_match('/^#/',$key)) {
      print render($element[$key]);
    }
  }
?>