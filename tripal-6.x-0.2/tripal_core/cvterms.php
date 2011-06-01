<?php
//
// Copyright 2009 Clemson University
//

/************************************************************************
*
*/
function tripal_add_cvterms ($name,$definition,$cv_name = 'tripal',$db_name='tripal'){
   
   
   $previous_db = db_set_active('chado');  // use chado database
   $cv = db_fetch_object(db_query("SELECT * FROM {cv} WHERE name = '$cv_name'"));
   if (!$cv->cv_id) {
      db_set_active($previous_db);
      tripal_add_cv('tripal', 'Terms used by Tripal for modules to manage data such as that stored in property tables like featureprop, analysisprop, etc');
      db_set_active('chado');
      $cv = db_fetch_object(db_query("SELECT * FROM {cv} WHERE name = '$cv_name'"));
   }
   $db = db_fetch_object(db_query("SELECT * FROM {db} WHERE name = '$db_name'"));
	if (!$db->db_id) {
	   db_set_active($previous_db);
	   tripal_add_db('tripal', 'Used as a database placeholder for tripal defined objects such as tripal cvterms', '', '');
	   db_set_active('chado');
	   $db = db_fetch_object(db_query("SELECT * FROM {db} WHERE name = '$db_name'"));
	}
	// check to see if the dbxref already exists if not then add it
	$sql = "SELECT * FROM {dbxref} WHERE db_id = $db->db_id and accession = '$name'";
	$dbxref = db_fetch_object(db_query($sql));
	if(!$dbxref){
	   db_query("INSERT INTO {dbxref} (db_id,accession) VALUES ($db->db_id,'$name')");
		$dbxref = db_fetch_object(db_query($sql));
	}

   
   // now add the cvterm only if it doesn't already exist
	$sql = "SELECT * FROM {cvterm} ".
	       "WHERE cv_id = $cv->cv_id and dbxref_id = $dbxref->dbxref_id and name = '$name'";
	$cvterm = db_fetch_object(db_query($sql));
	if(!$cvterm){
      $result = db_query("INSERT INTO {cvterm} (cv_id,name,definition,dbxref_id) ".
                         "VALUES ($cv->cv_id,'$name','$definition',$dbxref->dbxref_id)");
	}
   db_set_active($previous_db);  // now use drupal database	
	
   if(!$result){
     // TODO -- ERROR HANDLING
   }
}
/************************************************************************
*
*/
function tripal_add_db($db_name,$description,$urlprefix,$url){
   $previous_db = db_set_active('chado');  // use chado database

   // use this SQL statement to get the db_id for the database name
   $id_sql = "SELECT db_id FROM {db} WHERE name = '%s'";

   $db = db_fetch_object(db_query($id_sql,$db_name));

   // if the database doesn't exist then let's add it.
   if(!$db->db_id){
      $sql = "
         INSERT INTO {db} (name,description,urlprefix,url) VALUES 
         ('%s','%s','%s','%s');
      ";
      db_query($sql,$db_name,$description,$urlprefix,$url);
    
      # now get the id for this new db entry
      $db = db_fetch_object(db_query($id_sql,$db_name));
   }
   db_set_active($previous_db);  // now use drupal database	
   return $db->db_id;
}

/************************************************************************
*
*/
function tripal_delete_db($db_name){
   $previous_db = db_set_active('chado');  // use chado database
   $sql = "DELETE FROM {db} WHERE name ='%s'";
   db_query($sql,$db_name);
   db_set_active($previous_db);  // now use drupal database 
   
}

/************************************************************************
*
*/
function tripal_add_cv($cv_name,$definition){
   $previous_db = db_set_active('chado');  // use chado database

   // use this SQL statement to get the db_id for the database name
   $id_sql = "SELECT cv_id FROM {cv} WHERE name = '%s'";

   $cv = db_fetch_object(db_query($sql,$cv_name));

   // if the database doesn't exist then let's add it.
   if(!$cv){
      $sql = "
         INSERT INTO {cv} (name,definition) VALUES 
         ('%s','%s');
      ";
      db_query($sql,$cv_name,$definition);
    
      # now get the id for this new db entry
      $cv = db_fetch_object(db_query($sql,$cv_name));
   }
   db_set_active($previous_db);  // now use drupal database	
   return $cv->cv_id;
}
