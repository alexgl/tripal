//
// Copyright 2009 Clemson University
//

if (Drupal.jsEnabled) {
   $(document).ready(function() {
      $(".tripal_kegg_brite_tree").attr("id", function(){
			init_kegg_tree($(this).attr("id"));    
      });
   });

   //------------------------------------------------------------
   function tripal_analysis_kegg_org_report(item){
      if(!item){
         $("#tripal_analysis_kegg_org_report").html('');
         return false;
      }
      // Get the base url. Drupal can not pass it through the form so we need 
	  // to get it ourself. Use different patterns to match the url in case
      // the Clean URL function is turned on
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
     
      // Form the link for the following ajax call  
      var link = baseurl + '/tripal_analysis_kegg_org_report/' + item;
      tripal_startAjax();
      $.ajax({
           url: link,
           dataType: 'json',
           type: 'POST',
           success: function(data){
             $("#tripal_analysis_kegg_org_report").html(data[0]);
             $(".tripal_kegg_brite_tree").attr("id", function(){
			       init_kegg_tree($(this).attr("id"));    
             });
             tripal_stopAjax();
           }
      });
      return false;
   }
   
   //------------------------------------------------------------
   // Update the BRITE hierarchy based on the user selection
   function tripal_update_brite(link,type_id){
      tripal_startAjax();
      $.ajax({
         url: link.href,
         dataType: 'json',
         type: 'POST',
         success: function(data){
            $("#tripal_kegg_brite_hierarchy").html(data.update);
            $("#tripal_kegg_brite_header").html(data.brite_term);
            tripal_stopAjax();
            init_kegg_tree(data.id);
         }
      });
      return false;
   }

   //------------------------------------------------------------
   // This function initializes a KEGG term tree
   function init_kegg_tree(id){
	   // Get the base url. Drupal can not pass it through the form so we need 
	   // to get it ourself. Use different patterns to match the url in case
       // the Clean URL function is turned on
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
	   // Form the link for the following ajax call
       var link = baseurl + "/sites/all/themes/theme_tripal/js/jsTree/source/themes/";
	   
      $("#" + id).tree ({
	    data    : {
	        type    : "predefined", // ENUM [json, xml_flat, xml_nested, predefined]
	        method  : "GET",        // HOW TO REQUEST FILES
	        async   : false,        // BOOL - async loading onopen
	        async_data : function (NODE) { return { id : $(NODE).attr("id") || 0 } }, // PARAMETERS PASSED TO SERVER
	        url     : false,        // FALSE or STRING - url to document to be used (async or not)
	        json    : false,        // FALSE or OBJECT if type is JSON and async is false - the tree dump as json
	        xml     : false         // FALSE or STRING
	    },
        ui      : {
           dots        : true,     // BOOL - dots or no dots
           rtl         : false,    // BOOL - is the tree right-to-left
           animation   : 30,        // INT - duration of open/close animations in miliseconds
           hover_mode  : true,     // SHOULD get_* functions chage focus or change hovered item
           scroll_spd  : 4,
           theme_path  : link,    // Path to themes
           theme_name  : "classic",// Name of theme
        },

        rules   : {
           multiple    : false,    // FALSE | CTRL | ON - multiple selection off/ with or without holding Ctrl
           metadata    : false,    // FALSE or STRING - attribute name (use metadata plugin)
           type_attr   : "rel",    // STRING attribute name (where is the type stored if no metadata)
           multitree   : false,    // BOOL - is drag n drop between trees allowed
           createat    : "bottom", // STRING (top or bottom) new nodes get inserted at top or bottom
           use_inline  : false,    // CHECK FOR INLINE RULES - REQUIRES METADATA
           clickable   : "all",    // which node types can the user select | default - all
           renameable  : false,    // which node types can the user select | default - all
           deletable   : false,    // which node types can the user delete | default - all
           creatable   : false,    // which node types can the user create in | default - all
           draggable   : "none",   // which node types can the user move | default - none | "all"
           dragrules   : "all",    // what move operations between nodes are allowed | default - none | "all"
           drag_copy   : false,    // FALSE | CTRL | ON - drag to copy off/ with or without holding Ctrl
           droppable   : [],
           drag_button : "left"
        },

        callback    : {             // various callbacks to attach custom logic to
           // before focus  - should return true | false
           beforechange: function(NODE,TREE_OBJ) { return true },
           beforeopen  : function(NODE,TREE_OBJ) { return true },
           beforeclose : function(NODE,TREE_OBJ) { return true },
           // before move   - should return true | false
           beforemove  : function(NODE,REF_NODE,TYPE,TREE_OBJ) { return true }, 
           // before create - should return true | false
           beforecreate: function(NODE,REF_NODE,TYPE,TREE_OBJ) { return true }, 
           // before rename - should return true | false
           beforerename: function(NODE,LANG,TREE_OBJ) { return true }, 
           // before delete - should return true | false
           beforedelete: function(NODE,TREE_OBJ) { return true }, 

           onJSONdata  : function(DATA,TREE_OBJ) { return DATA; },
           onselect    : function(NODE,TREE_OBJ) { tripal_cv_cvterm_info( $(NODE).attr("id"),vars )},                  // node selected
           ondeselect  : function(NODE,TREE_OBJ) { },                  // node deselected
           onchange    : function(NODE,TREE_OBJ) { },                  // focus changed
           onrename    : function(NODE,LANG,TREE_OBJ,RB) { },              // node renamed ISNEW - TRUE|FALSE, current language
           onmove      : function(NODE,REF_NODE,TYPE,TREE_OBJ,RB) { }, // move completed (TYPE is BELOW|ABOVE|INSIDE)
           oncopy      : function(NODE,REF_NODE,TYPE,TREE_OBJ,RB) { }, // copy completed (TYPE is BELOW|ABOVE|INSIDE)
           oncreate    : function(NODE,REF_NODE,TYPE,TREE_OBJ,RB) { }, // node created, parent node (TYPE is createat)
           ondelete    : function(NODE, TREE_OBJ,RB) { },                  // node deleted
           onopen      : function(NODE, TREE_OBJ) { },                 // node opened
           onopen_all  : function(TREE_OBJ) { },                       // all nodes opened
           onclose     : function(NODE, TREE_OBJ) { },                 // node closed
           error       : function(TEXT, TREE_OBJ) { },                 // error occured
           // double click on node - defaults to open/close & select
           ondblclk    : function(NODE, TREE_OBJ) { TREE_OBJ.toggle_branch.call(TREE_OBJ, NODE); TREE_OBJ.select_branch.call(TREE_OBJ, NODE); },
           // right click - to prevent use: EV.preventDefault(); EV.stopPropagation(); return false
           onrgtclk    : function(NODE, TREE_OBJ, EV) { },
           onload      : function(TREE_OBJ) { },
           onfocus     : function(TREE_OBJ) { },
           ondrop      : function(NODE,REF_NODE,TYPE,TREE_OBJ) {}
        }
      });
   }
}
