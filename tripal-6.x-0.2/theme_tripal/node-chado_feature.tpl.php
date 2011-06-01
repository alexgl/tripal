<?php
/*
* Copyright 2009 Clemson University
*/
?>

   <?php if ($picture) {
      print $picture;
   }?>

   <div class="node<?php if ($sticky) { print " sticky"; } ?><?php if (!$status) { print " node-unpublished"; } ?>">

   <?php if ($page == 0) { ?><h2 class="nodeTitle"><a href="<?php print $node_url?>"><?php print $title?></a>
	<?php global $base_url;
	if ($sticky) { print '<img src="'.base_path(). drupal_get_path('theme','sanqreal').'/img/sticky.gif" alt="sticky icon" class="sticky" />'; } ?>
	</h2><?php }; ?>

	<?php if (!$teaser): ?>
	   <?php if ($submitted): ?>
      <div class="metanode"><p><?php print t('') .'<span class="author">'. theme('username', $node).'</span>' . t(' - Posted on ') . '<span class="date">'.format_date($node->created, 'custom', "d F Y").'</span>'; ?></p></div>
      <?php endif; ?>
      <!-- theme_tripal_feature_feature_id -->
      <!--<div id="feature_notice"><img src="sites/all/modules/tripal_analysis_blast/images/info-128x128.png"><br><i>Feature information and annotations have moved. See below</i></div>-->
      <div id="feature-view">
         <?php
            $aprefix = variable_get('chado_feature_accession_prefix','ID');
            $feature = $node->feature;
            if($feature->is_obsolete == 't'){
            drupal_set_message(t('This feature is obsolete and no longer used in analysis, but is here for reference.'));
         }?>
         <table class="tripal_table_vert">
            <tr><th>Name</th><td><?php print $feature->featurename; ?></td></tr>
            <tr><th>Accession</th><td><?php print variable_get('chado_feature_accession_prefix','ID'); print $feature->feature_id; ?></td></tr>
            <tr><th valign="top">Sequence</th><td><pre><?php print ereg_replace("(.{50})","\\1<br>",$feature->residues); ?></pre></td></tr>
            <tr><th>Length</th><td><?php print $feature->seqlen ?></td></tr>
            <tr><th>Type</th><td><?php print $feature->cvname; ?></td>
            </tr>
            <?php $org_url = url("node/$org_nid")?>
            <tr>
            	<th>Organism</th>
            	<td>
            		<?php if ($org_nid) {?>
            				<a href="<?php print $org_url?>"><?php print $feature->common_name?></a>
            		<?php
            		      } else {
            		         if ($feature->common_name) {
                               print $feature->common_name;
            		         } else {
            		           // This sql is for the preview to show organism common_name
            		           $sql = "SELECT common_name FROM organism WHERE organism_id = $node->organism_id";
                               $previous_db = db_set_active('chado');
                               $org_commonname = db_result(db_query($sql));
                               print $org_commonname;
                               db_set_active($previous_db);
            		         }
            		      }
            		?>
            	</td>
           	</tr>
           	
           	<!-- Add library information which this feature belongs to-->
           	<?php if ($node->lib_additions) { ?>
               <tr><th>Library</th><td>
                  <?php
                     $libraries = $node->lib_additions;
                     foreach ($libraries as $lib_url => $lib_name) {
                        // Check if library exists as a node in drupal
                        if ($lib_url) {
                  ?>
                     <a href="<?php print $lib_url?>"><?php print $lib_name?></a><BR>
                  <?php
                        } else {
                           print $lib_name;
                        }
                     }
                  ?>
               </td></tr>
            <?php } ?>
            <!-- End of library addition -->
            
            <!-- theme_tripal_feature_feature_synonyms -->
            <?php
               $synonyms = $node->synonyms;
               if(count($synonyms) > 0){
            ?>
      			<tr><th>Synonyms</th><td>
                  <?php
                  // iterate through each synonym
                  if (is_array($synonyms)) {
                     foreach ($synonyms as $result){
                        print $result->name."<br>";
                     }
                  } else {
                     print $synonyms;
                  }
                  ?>
               	</td></tr>
            <?php } ?>
      		<!-- End of theme_tripal_feature_feature_synonyms -->
         </table>
      </div>
      <!-- End of theme_tripal_feature_feature_id -->

   <?php endif; ?>

   <div class="content">
   <?php if (!$teaser): ?>
     <!-- Control link for the expandableBoxes -->
       <br><a id="tripal_expandableBox_toggle_button" onClick="toggleExpandableBoxes()">[-] Collapse All</a><br><br>
     <!-- End of Control link for the expandableBoxes -->
     <!-- theme_tripal_feature_feature_references -->
      <?php
         $references = $node->references;
         if(count($references) > 0){
      ?>
      <div id="feature-references" class="tripal_feature-info-box">
      <div class="tripal_expandableBox"><h3>References</h3></div>
      <div class="tripal_expandableBoxContent">
      <table>
         <tr>
            <th>Dababase</th>
            <th>Accession</th>
         </tr>
      <?php
         foreach ($references as $result){
      ?>
         <tr>
            <td><?php print $result->db_name?></td>
            <td><?php if($result->urlprefix){ ?><a href="<?php print $result->urlprefix.$result->accession?>"><?php print $result->accession?></a><?php } else { print $result->accession; } ?></td>
         </tr>
      <?php  } ?>
         </table></div></div>
      <?php } ?>
     <!-- End of theme_tripal_feature_feature_references -->
   <?php endif; ?>
   <?php print $content?>
   </div>
   
   <?php if (!$teaser): ?>
   <?php if ($links) { ?><div class="links"><?php print $links?></div><?php }; ?>
   <?php endif; ?>

   <?php if ($teaser): ?>
   <?php if ($links) { ?><div class="linksteaser"><div class="links"><?php print $links?></div></div><?php }; ?>
   <?php endif; ?>

   <?php if (!$teaser): ?>
   <?php if ($terms) { ?><div class="taxonomy"><span><?php print t('tags') ?></span> <?php print $terms?></div><?php } ?>
   <?php endif; ?>

   </div>
