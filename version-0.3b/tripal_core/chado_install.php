<?php

/**
*
*
* @ingroup tripal_core
*/
function tripal_core_chado_v1_11_load_form (){
   $form['description'] = array(
       '#type' => 'item',
       '#value' => t("Click the submit button below to install Chado into the Drupal database. <br><font color=\"red\">WARNING:</font> use this only for a new chado installation or reinstall completely.  This will erase any data currently in the chado database.  If you are 
using chado in a database external to the Drupal database with a 'chado' entry in the 'settings.php' \$db_url argument then this option will intall chado but it will not be usable.  The external database specified in the settings.php file takes precedence."),
       '#weight' => 1,
   );
   $form['button'] = array(
      '#type' => 'submit',
      '#value' => t('Install Chado'),
      '#weight' => 2,
   );
   return $form;
}
/**
*
* @ingroup tripal_core
*/
function tripal_core_chado_v1_11_load_form_submit ($form, &$form_state){
   global $user;

   $args = array();
   tripal_add_job("Install Chado",'tripal_core',
      'tripal_core_install_chado',$args,$user->uid);

   return '';
}
/**
*
*
* @ingroup tripal_core
*/
function tripal_core_install_chado ($dummy = NULL, $job = NULL){
   $schema_file = drupal_get_path('module', 'tripal_core').'/default_schema.sql';
   $init_file = drupal_get_path('module', 'tripal_core').'/initialize.sql';
   tripal_core_reset_chado_schema();
   tripal_core_install_sql($schema_file);
   tripal_core_install_sql($init_file);      
}
/**
*
*
* @ingroup tripal_core
*/
function tripal_core_reset_chado_schema (){
   global $active_db;

   // iterate through the lines of the schema file and rebuild the SQL
   pg_query($active_db,"drop schema chado cascade");
   pg_query($active_db,"drop schema genetic_code cascade");
   pg_query($active_db,"drop schema so cascade");
   pg_query($active_db,"drop schema frange cascade");
   pg_query($active_db,"create schema chado");
   pg_query($active_db,"create language plpgsql");
}
/**
*
*
* @ingroup tripal_core
*/
function tripal_core_install_sql ($sql_file){
   global $active_db;

   pg_query($active_db,"set search_path to chado,public");

   print "Loading $sql_file...\n";
   $lines = file($sql_file,FILE_SKIP_EMPTY_LINES);
   if(!$lines){
      return 'Cannot open $schema_file';
   }

   $stack = array();
   $in_string = 0;

   $query = '';
   $i = 0;
   foreach ($lines as $line_num => $line) {
      $i++;
      $type = '';

      // find and remove comments except when inside of strings
      if(preg_match('/--/',$line) and !$in_string and !preg_match("/'.*?--.*?'/",$line)){
         $line = preg_replace('/--.*$/','',$line);  // remove comments
      }
      if(preg_match('/\/\*.*?\*\//',$line)){
         $line = preg_replace('/\/\*.*?\*\//','',$line);  // remove comments
      }
      // skip empty lines
      if(preg_match('/^\s*$/',$line) or strcmp($line,'')==0){
         continue;
      }

      // Find SQL for new objects
      if(preg_match('/^\s*CREATE\s+TABLE/i',$line) and !$in_string){
         $stack[] = 'table';
         $line = preg_replace("/public./","chado.",$line);
      }
      if(preg_match('/^\s*ALTER\s+TABLE/i',$line) and !$in_string){
         $stack[] = 'alter table';
         $line = preg_replace("/public./","chado.",$line);
      }
      if(preg_match('/^\s*SET/i',$line) and !$in_string){
         $stack[] = 'set';
      }
      if(preg_match('/^\s*CREATE\s+SCHEMA/i',$line) and !$in_string){
         $stack[] = 'schema';
      }
      if(preg_match('/^\s*CREATE\s+SEQUENCE/i',$line) and !$in_string){
         $stack[] = 'sequence';
         $line = preg_replace("/public./","chado.",$line);
      }
      if(preg_match('/^\s*CREATE\s+(?:OR\s+REPLACE\s+)*VIEW/i',$line) and !$in_string){
         $stack[] = 'view';
         $line = preg_replace("/public./","chado.",$line);
      }
      if(preg_match('/^\s*COMMENT/i',$line) and !$in_string and sizeof($stack)==0){
         $stack[] = 'comment';  
         $line = preg_replace("/public./","chado.",$line);
      }
      if(preg_match('/^\s*CREATE\s+(?:OR\s+REPLACE\s+)*FUNCTION/i',$line) and !$in_string){
         $stack[] = 'function';
         $line = preg_replace("/public./","chado.",$line);
      }
      if(preg_match('/^\s*CREATE\s+INDEX/i',$line) and !$in_string){
         $stack[] = 'index';
      }
      if(preg_match('/^\s*INSERT\s+INTO/i',$line) and !$in_string){
         $stack[] = 'insert';
         $line = preg_replace("/public./","chado.",$line);
      }
      if(preg_match('/^\s*CREATE\s+TYPE/i',$line) and !$in_string){
         $stack[] = 'type';
      }
      if(preg_match('/^\s*GRANT/i',$line) and !$in_string){
         $stack[] = 'grant';
      }
      if(preg_match('/^\s*CREATE\s+AGGREGATE/i',$line) and !$in_string){
         $stack[] = 'aggregate';
      }

      // determine if we are in a string that spans a line
      $matches = preg_match_all("/[']/i",$line,$temp);
      $in_string = $in_string - ($matches % 2);
      $in_string = abs($in_string);

      // if we've reached the end of an object the pop the stack 
      if(strcmp($stack[sizeof($stack)-1],'table') == 0 and preg_match('/\);\s*$/',$line)){
         $type = array_pop($stack);
      }
      if(strcmp($stack[sizeof($stack)-1],'alter table') == 0 and preg_match('/;\s*$/',$line) and !$in_string){
         $type = array_pop($stack);
      }
      if(strcmp($stack[sizeof($stack)-1],'set') == 0 and preg_match('/;\s*$/',$line) and !$in_string){
         $type = array_pop($stack);
      }
      if(strcmp($stack[sizeof($stack)-1],'schema') == 0 and preg_match('/;\s*$/',$line) and !$in_string){
         $type = array_pop($stack);
      }
      if(strcmp($stack[sizeof($stack)-1],'sequence') == 0 and preg_match('/;\s*$/',$line) and !$in_string){
         $type = array_pop($stack);
      }
      if(strcmp($stack[sizeof($stack)-1],'view') == 0 and preg_match('/;\s*$/',$line) and !$in_string){
         $type = array_pop($stack);
      }
      if(strcmp($stack[sizeof($stack)-1],'comment') == 0 and preg_match('/;\s*$/',$line) and !$in_string){
         $type = array_pop($stack);
      }
      if(strcmp($stack[sizeof($stack)-1],'function') == 0 and preg_match("/LANGUAGE.*?;\s+$/i",$line)){
         $type = array_pop($stack);
      }
      if(strcmp($stack[sizeof($stack)-1],'index') == 0 and preg_match('/;\s*$/',$line) and !$in_string){
         $type = array_pop($stack);
      }
      if(strcmp($stack[sizeof($stack)-1],'insert') == 0 and preg_match('/\);\s*$/',$line)){
         $type = array_pop($stack);
      }
      if(strcmp($stack[sizeof($stack)-1],'type') == 0 and preg_match('/\);\s*$/',$line)){
         $type = array_pop($stack);
      }
      if(strcmp($stack[sizeof($stack)-1],'grant') == 0 and preg_match('/;\s*$/',$line) and !$in_string){
         $type = array_pop($stack);
      }
      if(strcmp($stack[sizeof($stack)-1],'aggregate') == 0 and preg_match('/\);\s*$/',$line)){
         $type = array_pop($stack);
      }


      // if we're in a recognized SQL statement then let's keep track of lines
      if($type or sizeof($stack) > 0){
         $query .= "$line";
      } else {
         print "UNHANDLED $i, $in_string: $line";
         return tripal_core_chado_install_done();
      }

      if(preg_match_all("/\n/",$query,$temp) > 100){
         print "SQL query is too long.  Terminating:\n$query\n";
         return tripal_core_chado_install_done();
      }

      if($type and sizeof($stack) == 0){
         print "Adding $type: line $i\n";
         // rewrite the set serach_path to make 'public' be 'chado'
         if(strcmp($type,'set')==0){
            $query = preg_replace("/public/m","chado",$query);
         }
         $result = pg_query($active_db, $query);
         if(!$result){
            $error  = pg_last_error();
            print "Installation failed:\nSQL $i, $in_string: $query\n$error\n";
            pg_query($active_db,"set search_path to public,chado");  
            return tripal_core_chado_install_done();
         }
         $query = '';
      }    
   }

   print "Installation Complete!\n";
   tripal_core_chado_install_done(); 
}
/**
*
*
* @ingroup tripal_core
*/
function tripal_core_chado_install_done (){
   // return the search path to normal
   global $active_db;
   pg_query($active_db,"set search_path to public,chado");  
}
