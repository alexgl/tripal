<?php
// Copyright 2010 University of Saskatchewan (Lacey-Anne Sanderson)
//
// Purpose: Provides layout and content for Synonyms of the Current Stock.
//   Stock synonyms are stored in the stockprop table with a type corresponding
//   to the cvterm synonym.
//
// Note: This template controls the layout/content for the default stock node
//   template (node-chado_stock.tpl.php) and the Stock Synonyms Block
//
// Variables Available:
//   - $node: a standard object which contains all the fields associated with
//       nodes including nid, type, title, taxonomy. It also includes stock
//       specific fields such as stock_name, uniquename, stock_type, synonyms,
//       properties, db_references, object_relationships, subject_relationships,
//       organism, etc.
//   - $node->synonyms: an array of stock property objects where each object
//       has type=synonyms and the following fields: stockprop_id, type_id,
//       type, value, rank
//   NOTE: For a full listing of fields available in the node object the
//       print_r $node line below or install the Drupal Devel module which 
//       provides an extra tab at the top of the node page labelled Devel
?>

<?php
  $node->stock->stock_synonyms = tripal_core_generate_chado_var(
    'stockprop', 
    array(
      'stock_id'=>$node->stock->stock_id,
      'type_id' => array(
        'cv_id' => variable_get('chado_stock_prop_types_cv', 'null'),
        'name' => 'synonym'
      ),
    )
  );
  $synonyms = $node->stock->stock_synonyms;
  if (!$synonyms) {
    $synonyms = array();
  } elseif (!is_array($synonyms)) { 
    $synonyms = array($synonyms); 
  }
?>

<?php
 //uncomment this line to see a full listing of the fields avail. to $node
 //print '<pre>'.print_r($node,TRUE).'</pre>';
?>

<div id="tripal_stock-synonyms-box" class="tripal_stock-info-box tripal-info-box">
  <div class="tripal_stock-info-box-title tripal-info-box-title">Synonyms</div>
  <div class="tripal_stock-info-box-desc tripal-info-box-desc">Synonyms for the stock '<?php print $node->stock->name ?>' include:</div>
	<?php if(count($synonyms) > 0){
		print '<ul>';
			// iterate through each synonym
			foreach ($synonyms as $result){
				print '<li>'.$result->value.'</li>';
			}
		print '</ul>';
	} else {
	  print '<b>There are no synonyms for the current stock.</b>';
	} ?>
</div>