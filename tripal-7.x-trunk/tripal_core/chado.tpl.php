
<?php
 $entity = $element['#entity'];
?>

<!-- Authoring information -->
<?php
$creation_user = user_load($entity->created_by);
$modification_user = user_load($entity->last_modified_by);
?>

<h2>Author Information</h2>
 <table id="chado-authoring-table-base" class="chado-authoring-table tripal-table tripal-table-vert">
    <tr class="chado-authoring-table-odd-row tripal-table-even-row">
      <th>Created By</th>
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