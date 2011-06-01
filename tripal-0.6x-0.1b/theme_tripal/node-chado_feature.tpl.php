<?php
//
// Copyright 2009 Clemson University
//
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
      <div class="metanode"><p><?php print t('') .'<span class="author">'. theme('username', $node).'</span>' . t(' - Posted on ') . '<span class="date">'.format_date($node->created, 'custom', "d F Y").'</span>'; ?></p>
      </div>
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
      <!-- theme_tripal_feature_feature_references -->
      <?php
         $references = $node->references;
         if(count($references) > 0){
      ?>
      <div id="feature-references" class="feature-info-box">
      <div class="tripal_expandableBox"><h3>References</h3></div>
      <div class="tripal_expandableBoxContent">
      <table>
         <tr>
            <th>Dababase</th>
            <th>Accession</th>
         </tr>
      <?php
         // iterate through each blast result
         foreach ($references as $result){
      ?>
         <tr>
            <td><?php print $result->db_name?></td>
            <td><?php if($result->urlprefix){ ?><a href="<?php print $result->urlprefix.$result->accession?>"><?php print $result->accession?></a><?php } else { print $result->accession; } ?></td>
         </tr>
      <?php  } ?>
      	</table></div>
      <?php } ?>
      <!-- End of theme_tripal_feature_feature_references -->
      <!-- theme_tripal_feature_feature_analyses -->
      <?php
         //If there is analysis associated with this feature
         $analysisfeature = $node->analysisfeature; // analyses selected by user in the form
         //$analysisfeature will be empty for node view. It will be filled only
         // when previewing the node
         $analyses = $node->analyses; // analyses stored in analysisfeature table
         if ($analysisfeature) {
            $analyses = array ();
         }
         $i = 0;
         $previous_db = db_set_active('chado');
         foreach ($analysisfeature as $value) {
            if ($value != 0) {
               $sql = "SELECT * FROM analysis WHERE analysis_id = $value";
               $results = db_query($sql);
               $data = db_fetch_object($results);
               $analyses [$i] = $data;
            }
            $i ++;
         }
         db_set_active($previous_db);
         if(count($analyses) > 0 && $analyses[0]->program){
      ?>
      <br><div id="feature-analyses" class="feature-info-box">
      <div class="tripal_expandableBox"><h3>Analyses performed on this feature</h3></div>
      <div class="tripal_expandableBoxContent">
      <table>
         <tr>
            <th>Name</th>
            <th>Program (version)</th>
            <th>Source</th>
         </tr>
      <?php
         // iterate through each blast result
         foreach ($analyses as $result){
      ?>
         <tr>
            <td><?php print $result->name?></td>
            <td><?php print $result->program." (".$result->programversion.")" ?></td>
            <td><?php print $result->sourcename ?></td>
         </tr>
      <?php  } ?>
      	</table></div>
      </div>
      <?php } ?>
      <!-- End of theme_tripal_feature_feature_analyses -->

<!--TODO: Move the theme functions to node tamplate file. No views for these themes
 are available at this time so the following themes can not be tested -->

<!-- theme_tripal_feature_feature_alignments -->
<?php
   $alignments = $node->alignments;
   if(count($alignments) > 0){
   ?>
     <div id="feature-alignments" class="feature-info-box">
      <!--we're showing contig alignments in GBrowse so create a link here for that
          if this feature is a contig-->

      <?php if($node->feature->cvname == 'contig'){ ?>
         <div class="tripal_feature_expandableBox"><h3>ESTs in this contig</h3></div>
         <div class="tripal_feature_expandableBoxContent">
      <?php } else { ?>
         <div class="tripal_feature_expandableBox"><h3>Alignments</h3></div>
         <div class="tripal_feature_expandableBoxContent">
      <?php } ?>
      <table>
         <tr>
            <th>Type</th>
            <th>Feature</th>
            <th align="right">Position</th>
         </tr>
      <!--iterate through each alignment-->
      <?php foreach ($alignments as $result){
         // EST alignments in chado use an EST_match type to map ESTs to
         // contigs and a rank to indicate the major srcfeature.
         // We don't want to show EST_matches on the alignment view
         // since that doesn't make much sense to the end user.  If this
         // is an EST_match and the feature is an EST then we want to show
         // the contig in the alignments.  The contig name is part of the
         // uniquename in the EST_match
         if($node->feature->cvname == 'EST' && $result->cvname == 'EST_match'){
            // extract the contig name from the EST_match name
            $contig = preg_replace("/^(.*?Contig_\d+?.*v\d+).*$/","$1",$result->uniquename);
            $sql = "SELECT vid FROM {node} WHERE title = '$contig'".
                   "ORDER BY vid DESC";
            // since the feature name is also the node title we can look it up
            $contig_node = db_fetch_object(db_query($sql));
      ?>
         <tr>
            <td>Contig</td>
            <td><a href="/node/<?php print $contig_node->vid?>"><?php print $contig ?></a></td>
            <td align="right"><?php print number_format($result->fmin)?>-<?php print number_format($result->fmax)?></td>
         </tr>
      <?php   }
         elseif($node->feature->cvname == 'contig' && $result->cvname == 'EST_match'){
            $sql = "SELECT vid FROM {node} WHERE title = '$result->feature_name'".
                   "ORDER BY vid DESC";
            // since the feature name is also the node title we can look it up
            $est_node = db_fetch_object(db_query($sql));
      ?>
            <tr>
               <td>EST</td>
               <td><a href="/node/<?php print $est_node->vid?>"><?php print $result->feature_name?></a></td>
               <td align="right"><?php print number_format($result->fmin)?>-<?php print number_format($result->fmax)?></td>
            </tr>
      <?php } else { ?>
            <tr>
               <td><?php print $result->cvname?></td>
               <td><?php print $result->feature_name?></td>
               <td align="right"><?php print $result->fmin?></td>
               <td align="right"><?php print $result->fmax?></td>
               <td align="right"><?php print $result->strand?></td>
            </tr>
      <?php   }
      }?>
      </table>

	  <!-- if this is a contig then get the alignment-->
	  <?php if($node->feature->cvname == 'contig'){
	     // get the directory prefix
		   $prefix = preg_replace("/^(\d*)\d{3}$/","$1",$node->feature_id);
		   if(!$prefix){
			   $prefix = '0';
		   }
         $data_url = variable_get('chado_feature_data_url','sites/default/files/data');
	      $fh = fopen("$data_url/misc/$prefix/$node->feature->feature_id/alignment.txt", 'r');
         if($fh){
      ?>
			<b>Alignment:</b><div class="tripal_feature_assembly_alignment"><pre>
      <?php      while(!feof($fh)){ ?>
				   <?php print fgets($fh)?>
				</pre></div>
		<?php	}
         fclose($fh);
	  }?>
	  </div></div>

   <?php }?>
<!-- End of theme_tripal_feature_feature_alignments -->

<!-- theme_tripal_feature_feature_go_terms -->
<?php
   $go_terms = $node->go_terms;
   if(count($go_terms) > 0){ ?>

   <div id="feature-goterms" class="feature-info-box">
         <div class="tripal_feature_expandableBox"><h3>Putative GO Assignments (extracted from the GO-SeqDB blast hits)</h3></div>
         <div class="tripal_feature_expandableBoxContent">
         <table>
            <tr>
               <th>GO Accession</th>
               <th>GO Description</th>
            </tr>

      <?php
      //iterate through each blast result
      foreach ($go_terms as $result){ ?>
         <tr>
            <td><?php print $result->accession?></td>
            <td><?php print $result->goterm?></td>
         </tr>
      <?php } ?>
      </table></div></div>
   <?php } ?>
<!-- End of theme_tripal_feature_feature_go_terms -->

<!-- theme_tripal_feature_feature_properties -->
<?php
   $properties = $node->properties;
   if(count($properties) > 0){ ?>
      <div id="feature-properties" class="feature-info-box">
      <div class="tripal_feature_expandableBox"><h3>Feature Properties</h3></div>
      <div class="tripal_feature_expandableBoxContent">
      <table>

      <?php // iterate through each blast result
      foreach ($properties as $result){
         $property = $result->name;
         if($property == 'unigene_version'){
            $property = 'Unigene version';
         }
         if($property == 'singlet'){
            $property = 'Singlet in unigne version';
         }

         // we don't want to show the drupal_date property
         // since this is an internal value, so skip it
         if($property == 'drupal_date'){
            continue;
         }
         ?>
         <tr>
            <td><?php print $property?>:</td>
            <td><?php print $result->value?></td>
         </tr>
      <?php }?>
      </table></div></div>
   <?php }?>
<!-- End of theme_tripal_feature_feature_properties -->

<!-- END OF TODO-->
        </div>
        <?php if ($links) { ?>
            <div class="links"> <?php print $links?></div>
        <?php }; ?>
    </div>

<?php }; ?>
   <?php endif; ?>
   <?php endif; ?>

   <div class="content"><?php print $content?></div>
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
