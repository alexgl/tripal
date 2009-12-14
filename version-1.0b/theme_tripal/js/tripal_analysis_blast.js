//
// Copyright 2009 Clemson University
//

if (Drupal.jsEnabled) {
   
   $(document).ready(function(){
      // hide the Tripal Blast checkbox if .blast-info-box doesn't exist
      var feature = $(".blast-info-box").get();
      if (feature=='') {
         $("#tripal_analysis_checkbox").hide();
      }
   });
  
   //------------------------------------------------------------
   // Update the blast results based on the user selection
   function tripal_update_blast(link,db_id){
      tripal_startAjax();
      $.ajax({
         url: link.href,
         dataType: 'json',
         type: 'POST',
         success: function(data){         
            $("#blast_db_" + db_id).html(data.update);
            // make sure the newly added expandable boxes are closed
            tripal_set_dropable_subbox();
            tripal_stopAjax();
         }
      });
      return false;
   }
}
