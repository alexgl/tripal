<?php

//
// Copyright 2009 Clemson University
//

/*************************************************************************
*
*/
function tripal_cv_show_browser() {
  
   $content = drupal_get_form('tripal_cv_list_form');
   $content .= "
      <div id=\"cv_browser\"></div>
   ";
   return $content;
}


/*************************************************************************
*
*/
function tripal_cv_tree($tree_id){
  // parse out the tripal module name from the chart_id to find out 
  // which Tripal "hook" to call:
  $tripal_mod = preg_replace("/^(tripal_.+?)_cv_tree_(.+)$/","$1",$tree_id);
  if($tripal_mod){
     $callback = $tripal_mod . "_cv_tree";

     // now call the function in the module responsible for the tree.  This 
     // should call the tripal_cv_init_cv with the proper parameters set for
     // getting the cv_id of the vocabulary to use
     $opt = call_user_func_array($callback,array($tree_id));

     // we only need to return the cv_id for this function call.
     $json_array[] = $opt[cv_id];
  }
  $json_array[] = $tree_id;
  return drupal_json($json_array);
}


/*************************************************************************
*
*/
function tripal_cv_update_tree() {
   $content = array();
   $ontology = 'sequence';

   # get the id of the term to look up
   $cv = check_plain($_REQUEST['cv']);
   $term = check_plain($_REQUEST['term']);
   $tree_id = check_plain($_REQUEST['tree_id']);

   # get the options needed for this tree from the tripal module that 
   # wants to create the tree
   $tripal_mod = preg_replace("/^(tripal_.+?)_cv_tree_(.+)$/","$1",$tree_id);
   if($tripal_mod){
      $callback = $tripal_mod . "_cv_tree";
      $opt = call_user_func_array($callback,array($tree_id));
   }

   # get the CV root terms
   if(strcmp($term,'root')==0){
      if(!$cv){
        $cv = $opt[cv_id];
      }
      $content = tripal_cv_init_tree($cv,$opt[count_mview],
         $opt[cvterm_id_column],$opt[count_column],$opt[filter],$opt[label]);
   } 
   # get the children terms
   else {
      $content = tripal_cv_get_term_children($term,$opt[count_mview],
         $opt[cvterm_id_column],$opt[count_column],$opt[filter],$opt[label]);
   }
   drupal_json($content);
}
/*************************************************************************
*  name:  tripal_cv_init_cv
*  description:  This function returns the JSON array for the jsTree 
*    jQuery code that builds a tree for browsing the ontology.  This function
*    should be called to generate the root level branches of the tree.
*/
function tripal_cv_init_tree($cv_id,$cnt_table = null, $fk_column = null,
   $cnt_column = null, $filter = null, $label = null) {

   // get the list of root terms for the provided CV
   $sql = "
      SELECT *
      FROM {cv_root_mview} CRM
      WHERE cv_id = %d
   ";
   $previous_db = tripal_db_set_active('chado');
   $results = db_query($sql,$cv_id);
   tripal_db_set_active($previous_db); 

   // prepare the SQL statement that will allow us to pull out count 
   // information for each term in the tree.
   if($cnt_table){
      if(!$filter){
         $filter = '(1=1)'; 
      }
      $cnt_sql = "
         SELECT CVT.name, CVT.cvterm_id, CNT.$cnt_column as num_items
         FROM {$cnt_table} CNT 
          INNER JOIN cvterm CVT on CNT.$fk_column = CVT.cvterm_id 
         WHERE $filter AND CVT.cvterm_id = %d
         ORDER BY $cnt_column desc
      ";
   }
 
   while ($term = db_fetch_object($results)) {
      $name = $term->name;
      $count = 0;
      if($cnt_table){
         $previous_db = tripal_db_set_active('chado');
         $cnt_results = db_query($cnt_sql,$term->cvterm_id);
         tripal_db_set_active($previous_db); 
         while($cnt = db_fetch_object($cnt_results)){
            $count += $cnt->cnt;
         }
         if($count > 0){
            $name .= " ($count $label(s))";
         }
      } 
      $content[] = array(
           'attributes' => array (
           'id' => $term->cvterm_id,
        ),
        state => 'closed',
        data => $name,
        children => array (),
      );
   }

   return $content;

}
/*************************************************************************
*  name:  tripal_cv_get_term_children
*  description:  This function returns the JSON array for the jsTree 
*    jQuery code when expanding a term to view it's children.  
*/
function tripal_cv_get_term_children($cvterm_id,$cnt_table = null, 
   $fk_column = null,$cnt_column = null, $filter = null, $label = null) {
   # get the children for the term provided
   $sql = "
      SELECT CVTR.cvterm_relationship_id,CVTR.subject_id,
         CVT1.name as subject_name, CVT3.name as type_name, CVTR.type_id, 
         CVT2.name as object_name,CVTR.object_id 
      FROM {cvterm_relationship} CVTR
         INNER JOIN CVTerm CVT1 on CVTR.subject_id = CVT1.cvterm_id
         INNER JOIN CVTerm CVT2 on CVTR.object_id = CVT2.cvterm_id
         INNER JOIN CVTerm CVT3 on CVTR.type_id = CVT3.cvterm_id
         INNER JOIN CV on CV.cv_id = CVT1.cv_id
      WHERE CVTR.object_id = %d
      ORDER BY CVT1.name
   ";
   $previous_db = tripal_db_set_active('chado');
   $results = db_query($sql,$cvterm_id);
   tripal_db_set_active($previous_db);


   // prepare the SQL statement that will allow us to pull out count 
   // information for each term in the tree.
   if($cnt_table){
      if(!$filter){
         $filter = '(1=1)'; 
      }
      $cnt_sql = "
         SELECT CVT.name, CVT.cvterm_id, CNT.$cnt_column as num_items
         FROM {$cnt_table} CNT 
          INNER JOIN cvterm CVT on CNT.$fk_column = CVT.cvterm_id 
         WHERE $filter AND CVT.cvterm_id = %d
         ORDER BY $cnt_column desc
      ";
   }
   // populate the JSON content array
   while ($term = db_fetch_object($results)) {
      // count the number of items per term if requested
      $name = $term->subject_name;
      $count = 0;
      if($cnt_table){
         $previous_db = tripal_db_set_active('chado');
         $cnt_results = db_query($cnt_sql,$term->subject_id);
         tripal_db_set_active($previous_db); 
         while($cnt = db_fetch_object($cnt_results)){
            $count += $cnt->num_items;
         }
         if($count > 0){
            $name .= " (".number_format($count)." $label)";
            // check if we have any children if so then set the value
            $previous_db = tripal_db_set_active('chado');
            $children = db_fetch_object(db_query($sql,$term->subject_id));
            tripal_db_set_active($previous_db);
            $state = 'leaf';
            if($children){
               $state = 'closed';
            }
            $content[] = array(
               'attributes' => array (
                  'id' => $term->subject_id,
               ),
               state => $state,
               data => $name,
               children => array(),
            );
         }
      } else {
         // check if we have any children if so then set the value
         $previous_db = tripal_db_set_active('chado');
         $children = db_fetch_object(db_query($sql,$term->subject_id));
         tripal_db_set_active($previous_db);
         $state = 'leaf';
         if($children){
            $state = 'closed';
         }
         $content[] = array(
            'attributes' => array (
               'id' => $term->subject_id,
            ),
            state => $state,
            data => $name,
            children => array(),
         );
      }
   }
   $content[] = $cnt_sql;
   return $content;
}
/*************************************************************************
*
*/
function tripal_cv_init_browser($cv_id) {

   $content = "
        <div id=\"tripal_cv_cvterm_info_box\">
           <a href=\"#\" onclick=\"$('#tripal_cv_cvterm_info_box').hide()\" style=\"float: right\">Close [X]</a>
           <h3>Term Information</h3>
           <div id=\"tripal_cv_cvterm_info\"></div>
        </div>
        <div id=\"tripal_ajaxLoading\" style=\"display:none\">
           <div id=\"loadingText\">Loading...</div>
           <img src=\"$url\">
        </div> 
         <h3>Tree Browser</h3>
        <div id=\"browser\"</div></div>
   ";

   drupal_json(array('update' => "$content"));
}
/*************************************************************************
*
*/
function tripal_cv_cvterm_info($cvterm_id){

   # get the id of the term to look up
   $cv = check_plain($_REQUEST['cv']);
   $tree_id = check_plain($_REQUEST['tree_id']);

   // first get any additional information to add to the cvterm
   if(strcmp($tree_id,'undefined')!=0){
      $tripal_mod = preg_replace("/^(tripal_.+?)_cv_tree_(.+)$/","$1",$tree_id);
      if($tripal_mod){
         $callback = $tripal_mod . "_cvterm_add";
         $opt = call_user_func_array($callback,array($cvterm_id,$tree_id));
      }
   }

   $sql = "
      SELECT CVT.name as cvtermname, CVT.definition, CV.name as cvname,
         DBX.accession,DB.urlprefix,DB.db_id,DB.name as dbname
      FROM {CVTerm} CVT
        INNER JOIN CV on CVT.cv_id = CV.cv_id
        INNER JOIN dbxref DBX on CVT.dbxref_id = DBX.dbxref_id
        INNER JOIN DB on DBX.db_id = DB.db_id
      WHERE CVT.cvterm_id = %d
   ";
   $previous_db = tripal_db_set_active('chado');
   $cvterm = db_fetch_object(db_query($sql,$cvterm_id));
   tripal_db_set_active($previous_db);
   $sql = "
      SELECT CVTS.synonym, CVT.name as cvname
      FROM {cvtermsynonym} CVTS
        INNER JOIN cvterm CVT on CVTS.type_id = CVT.cvterm_id
      WHERE CVTS.cvterm_id = %d
    
   ";
   $previous_db = tripal_db_set_active('chado');
   $results = db_query($sql,$cvterm_id);
   tripal_db_set_active($previous_db);
   while($synonym = db_fetch_object($results)){
      $synonym_rows .= "<b>$synonym->cvname:</b>  $synonym->synonym<br>";
   }
   $accession = $cvterm->accession;
   if($cvterm->urlprefix){
      $accession = "<a href=\"$cvterm->urlprefix$cvterm->accession\">$cvterm->accession</a>";
   }
   $content = "
      <div id=\"cvterm\">
      <table>
        <tr><th>Term</th><td>$cvterm->cvtermname</td></tr>
        <tr><th>Accession</th><td>$accession</td></tr>
        <tr><th>Ontology</th><td>$cvterm->cvname</td></tr>
        <tr><th>Definition</th><td>$cvterm->definition</td></tr>
        <tr><th>Synonyms</th><td>$synonym_rows</td></tr>
        <tr><th>Internal ID</th><td>$cvterm_id</td></tr> 
   ";

   // now add in any additional options from a hook
   if($opt){
      foreach ($opt as $key=>$value){
         $content .= "<tr><th>$key</th><td>$value</td>";
      }
   }

   // close out the information table
   $content .= "
      </table>
      </div>
   ";
   drupal_json(array('update' => $content));
}
/*******************************************************************************
*/

function tripal_cv_list_form($form_state) {

   // get a list of db from chado for user to choose
    $sql = "
      SELECT DISTINCT CV.name,CV.cv_id
      FROM {cvterm_relationship} CVTR
         INNER JOIN cvterm CVT on CVTR.object_id = CVT.cvterm_id
         INNER JOIN CV on CV.cv_id = CVT.cv_id
   ";
   $previous_db = tripal_db_set_active('chado');  // use chado database
   $results = db_query ($sql);
   tripal_db_set_active($previous_db);
   $blastdbs = array();
   $cvs[''] = '';
   while ($cv = db_fetch_object($results)){
      $cvs[$cv->cv_id] = $cv->name;
   }

   $form['db_options'] = array(
      '#type' => 'value',
      '#value' => $cvs
   );        
   $form['cv_list'] = array(
      '#title' => t('CVs with relationships'),
      '#type' => 'select',
      '#description' => t('Choose the controlled vocabulary to browse'),
      '#options' => $form['db_options']['#value'],
      '#attributes' => array(
         'onChange' => "return tripal_cv_init_browser(this)",
      )
   );
   return $form;
}
