<?php
//
// Copyright 2009 Clemson University
//

/************************************************************************
 * Add a materialized view to the chado database to help speed data access.
 * @param name The name of the materialized view.
 * @param modulename The name of the module submitting the materailzed view (e.g. 'tripal_library')
 * @param mv_table The name of the table to add to chado. This is the table that can be queried.
 * @param mv_specs The table definition 
 * @param indexed The columns that are to be indexed
 * @param query The SQL query that loads the materialized view with data
 * @param special_index  
 * function
 * @return nothing
 */
function tripal_add_mview ($name,$modulename,$mv_table,$mv_specs,$indexed,$query,$special_index){

   $record = new stdClass();
   $record->name = $name;
   $record->modulename = $modulename;
   $record->mv_schema = 'DUMMY';
   $record->mv_table = $mv_table;
   $record->mv_specs = $mv_specs;
   $record->indexed = $indexed;
   $record->query = $query;
   $record->special_index = $special_index;

   // add the record to the tripal_mviews table and if successful
   // create the new materialized view in the chado schema
   if(drupal_write_record('tripal_mviews',$record)){
	
	   // just in case the mview already exists in chado 
      // let's delete it and recreate
// TODO -- check for the existence of the table before trying to drop
//		$previous_db = db_set_active('chado');  // use chado database
//      $sql = "DROP TABLE $mv_table";
//      db_query($sql);
//      db_set_active($previous_db);  // now use drupal database
		
      // now add the table for this view
      $sql = "CREATE TABLE $mv_table ($mv_specs, CONSTRAINT " .
             $mv_table . "_index UNIQUE ($indexed) )";
      $previous_db = db_set_active('chado');  // use chado database
      $results = db_query($sql);
      db_set_active($previous_db);  // now use drupal database
      if($results){
         drupal_set_message(t("View '$name' created"));
      } else {
         // if we failed to create the view in chado then
         // remove the record from the tripal_jobs table
         $sql = "DELETE FROM {tripal_mviews} ".
                "WHERE mview_id = $record->mview_id";
         db_query($sql);
      }
   }
}
/************************************************************************
*
*/
function tripal_mviews_action ($op,$mview_id){
   global $user;
   $args = array("$mview_id");
   
   // get this mview details
   $sql = "SELECT * FROM {tripal_mviews} WHERE mview_id = $mview_id ";
   $mview = db_fetch_object(db_query($sql));
   
   // add a job or perform the action based on the given operation
   if($op == 'update'){
      tripal_add_job("Update materialized view '$mview->name'",'tripal_core',
         'tripal_update_mview',$args,$user->uid);
	}
   if($op == 'delete'){
	   // remove the mview from the tripal_mviews table
	   $sql = "DELETE FROM {tripal_mviews} ".
             "WHERE mview_id = $mview_id";
      db_query($sql);
		
		// drop the table from chado
      $previous_db = db_set_active('chado');  // use chado database
      $sql = "DROP TABLE $mview->mv_table";
      db_query($sql);
      db_set_active($previous_db);  // now use drupal database
   }
   return '';
}
/************************************************************************
*
*/
function tripal_update_mview ($mview_id){
   $sql = "SELECT * FROM {tripal_mviews} WHERE mview_id = $mview_id ";
   $mview = db_fetch_object(db_query($sql));
   if($mview){
      $previous_db = db_set_active('chado');  // use chado database
	   $results = db_query("DELETE FROM $mview->mv_table");
      $results = db_query("INSERT INTO $mview->mv_table ($mview->query)");
      db_set_active($previous_db);  // now use drupal database
      if($results){
	     $record = new stdClass();
         $record->mview_id = $mview_id;
         $record->last_update = time();
		 drupal_write_record('tripal_mviews',$record,'mview_id');
		 return 1;
      } else {
	     // TODO -- error handling
	     return 0;
	  }
   }
}
/************************************************************************
*
*/
function tripal_mviews_report () {
   $mviews = db_query("SELECT * FROM {tripal_mviews} ORDER BY name");

   // create a table with each row containig stats for
   // an individual job in the results set.
   $output .= "<table class=\"border-table\">". 
              "  <tr>".
              "    <th nowrap></th>".
              "    <th>Name</th>".
              "    <th>Last_Update</th>".
              "    <th nowrap></th>".
              "  </tr>";
   
   while($mview = db_fetch_object($mviews)){
      if($mview->last_update > 0){
         $update = format_date($mview->last_update);
      } else {
         $update = 'Not yet populated';
      }
	  // build the URLs using the url function so we can handle installations where
	  // clean URLs are or are not used
	  $update_url = url("admin/tripal/tripal_mviews/action/update/$mview->mview_id");
	  $delete_url = url("admin/tripal/tripal_mviews/action/delete/$mview->mview_id");
	  // create the row for the table
      $output .= "  <tr>";
      $output .= "    <td><a href='$update_url'>Update</a></td>".
	             "    <td>$mview->name</td>".
                 "    <td>$update</td>".
                 "    <td><a href='$delete_url'>Delete</a></td>".
                 "  </tr>";
   }
   $output .= "</table>";
   return $output;
}
/************************************************************************
*
*/
function tripal_mviews_form(&$form_state = NULL){
   $form['name']= array(
      '#type'          => 'textfield',
      '#title'         => t('View Name'),
      '#description'   => t('Please enter the name for this materialized view.'),
      '#required'      => TRUE,
      '#default_value' => $form_state['values']['name'],
      '#weight'        => 1
   );

   $form['mv_table']= array(
      '#type'          => 'textfield',
      '#title'         => t('Table Name'),
      '#description'   => t('Please enter the Postgres table name that this view will generate in the database.  You can use the schema and table name for querying the view'),
      '#required'      => TRUE,
      '#default_value' => $form_state['values']['mv_table'],
      '#weight'        => 3
   );
   $form['mv_specs']= array(
      '#type'          => 'textarea',
      '#title'         => t('Table Definition'),
      '#description'   => t('Please enter the field definitions for this view. Each field should be separated by a comma or enter each field definition on each line.'),
      '#required'      => TRUE,
      '#default_value' => $form_state['values']['mv_specs'],
      '#weight'        => 4
   );
   $form['indexed']= array(
      '#type'          => 'textarea',
      '#title'         => t('Indexed Fields'),
      '#description'   => t('Please enter the field names (as provided in the table definition above) that will be indexed for this view.  Separate by a comma or enter each field on a new line.'),
      '#required'      => FALSE,
      '#default_value' => $form_state['values']['indexed'],
      '#weight'        => 5
   );
   $form['mvquery']= array(
      '#type'          => 'textarea',
      '#title'         => t('Query'),
      '#description'   => t('Please enter the SQL statement used to populate the table.'),
      '#required'      => TRUE,
      '#default_value' => $form_state['values']['mvquery'],
      '#weight'        => 6
   );
/*
   $form['special_index']= array(
      '#type'          => 'textarea',
      '#title'         => t('View Name'),
      '#description'   => t('Please enter the name for this materialized view.'),
      '#required'      => TRUE,
      '#default_value' => $form_state['values']['special_index'],
      '#weight'        => 7
   );
*/
   $form['submit'] = array (
     '#type'         => 'submit',
     '#value'        => t('Create View'),
     '#weight'       => 8,
     '#executes_submit_callback' => TRUE,
   );
   return $form;
}
/************************************************************************
*
*/
function tripal_mviews_form_submit($form, &$form_state){
   tripal_add_mview ($form_state['values']['name'],
                     'tripal_core',
                     $form_state['values']['mv_table'],
                     $form_state['values']['mv_specs'],
                     $form_state['values']['indexed'],
                     $form_state['values']['mvquery'],
                     $form_state['values']['special_index']);
   return '';
}
