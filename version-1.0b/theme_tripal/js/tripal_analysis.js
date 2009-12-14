//
// Copyright 2009 Clemson University
//

if (Drupal.jsEnabled) {
   //------------------------------------------------------------
   // On document load we want to make sure the analysis result is shown
   var path = '';
   var tripal_AjaxRequests = 0;
   //--------------------------------------------
   function tripal_startAjax(){
      $("#tripal_ajaxLoading").show();
      tripal_AjaxRequests++;
   }
   //--------------------------------------------
   function tripal_stopAjax(){
      tripal_AjaxRequests--;
      if(tripal_AjaxRequests == 0){
         $("#tripal_ajaxLoading").hide();
      }
   }

   //------------------------------------------------------------
   // On document load we want to make sure that the expandable boxes
   // are closed
   $(document).ready(function(){
      // setup the expandable boxes used for showing blast results
      tripal_set_dropable_box();
      tripal_set_dropable_subbox();
      var selected = location.hash;
      if(selected.substring(0,1) == '#'){
         $('#' + selected.substring(1)).next().show();
         $('#' + selected.substring(1)).css("background", "#E1CFEA");
      }
      
      // hide the transparent ajax loading popup
      $("#tripal_ajaxLoading").hide();      
   });

   //------------------------------------------------------------
   function tripal_set_dropable_box(){
      //$('.tripal_expandableBoxContent').hide();
      
      $('.tripal_expandableBox').hover(
         function() {
            $(this).css("text-decoration", "none");
            $(this).css("background-color", "#EEFFEE");
         } ,
         function() {
            $(this).css("text-decoration", "none");
            $(this).css("background-color", "#EEEEFF");
         }
      );
      $('.tripal_expandableBox').click(
         function() {
            $(this).next().slideToggle('fast',
            function(){
               // Get the base url, use different patterns to match it in case
               // the Clean URL function is on
               var baseurl = location.href.substring(0,location.href.lastIndexOf('/?q=/node'));
               if(!baseurl) {
            	   var baseurl = location.href.substring(0,location.href.lastIndexOf('/node'));
               }
               if (!baseurl) {
                  // This base_url is obtained when Clena URL function is off
                  var baseurl = location.href.substring(0,location.href.lastIndexOf('/?q=node'));
               }
               if (!baseurl) {
                  // The last possibility is we've assigned an alias path, get base_url till the last /
                  var baseurl = location.href.substring(0,location.href.lastIndexOf('/'));
               }
               
               if($(this).css("display") == "none" ){               
                  $(this).prev().css("background-image", "url('" + baseurl + "/sites/all/themes/tripal/images/arrow-down-48x48.png')");
                  $(this).prev().css("background-repeat","no-repeat");
                  $(this).prev().css("background-position","top right");
               } else {
                  $(this).prev().css("background-image", "url('" + baseurl + "/sites/all/themes/tripal/images/arrow-up-48x48.png')");
                  $(this).prev().css("background-repeat","no-repeat");
                  $(this).prev().css("background-position","top right");
               }
            });
         }
      );
   }
   //------------------------------------------------------------
   function tripal_set_dropable_subbox(){
      $('.tripal_expandableSubBoxContent').hide();
      $('.tripal_expandableSubBox').hover(
         function() {
            $(this).css("text-decoration", "underline");
         } ,
         function() {
            $(this).css("text-decoration", "none");
         }
      );
      $('.tripal_expandableSubBox').click(
         function() {
            // Find the width of the table column for the tripal_expandableSubBoxContent
            var width = $(this).parent().parent().width();
            width -= 35;
            // Traverse through html DOM objects to find tripal_expandableSubBoxContent and change its settings
            $(this).parent().parent().next().children().children().css("width", width + 'px');
            $(this).parent().parent().next().children().children().slideToggle('fast');
         }
      );
   }
   
   function show(id) {
      $(id).show();
   }
   function hide(id) {
      $(id).hide();
   }
}
