<?php
//
// Copyright 2009 Clemson University
//

/************************************************************************
*
*/
function tripal_add_cvterms ($name,$definition){
   
   
   $previous_db = db_set_active('chado');  // use chado database
   $cv = db_fetch_object(db_query("SELECT * FROM cv WHERE name = 'tripal'"));
   $db = db_fetch_object(db_query("SELECT * FROM db WHERE name = 'tripal'"));
	
	// check to see if the dbxref already exists if not then add it
	$sql = "SELECT * FROM dbxref WHERE db_id = $db->db_id and accession = '$name'";
	$dbxref = db_fetch_object(db_query($sql));
	if(!$dbxref){
	   db_query("INSERT INTO dbxref (db_id,accession) VALUES ($db->db_id,'$name')");
		$dbxref = db_fetch_object(db_query($sql));
	}

   
   // now add the cvterm only if it doesn't already exist
	$sql = "SELECT * FROM cvterm ".
	       "WHERE cv_id = $cv->cv_id and dbxref_id = $dbxref->dbxref_id and name = '$name'";
	$cvterm = db_fetch_object(db_query($sql));
	if(!$cvterm){
      $result = db_query("INSERT INTO cvterm (cv_id,name,definition,dbxref_id) ".
                         "VALUES ($cv->cv_id,'$name','$definition',$dbxref->dbxref_id)");
	}
   db_set_active($previous_db);  // now use drupal database	
	
   if(!$result){
     // TODO -- ERROR HANDLING
   }
}
 
