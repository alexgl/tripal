<?php
$node = $variables['node'];
$analysis = $variables['node']->analysis;
$interpro = $node->analysis->tripal_analysis_interpro;

// the description is a text field so we want to expand that
$analysis = tripal_core_expand_chado_vars($analysis,'field','analysis.description');
?>
<div id="tripal_analysis_interpro-base-box" class="tripal_analysis_interpro-info-box tripal-info-box">
  <div class="tripal_analysis_interpro-info-box-title tripal-info-box-title">Details</div>
   <table id="tripal_analysis_interpro-table-base" class="tripal_analysis_interpro-table tripal-table tripal-table-vert">
      <tr class="tripal_analysis_interpro-table-even-row tripal-table-even-row">
        <th>Analysis Name</th>
        <td><?php print $analysis->name; ?></td>
      </tr>
      <tr class="tripal_analysis_interpro-table-odd-row tripal-table-odd-row">
        <th nowrap>Software</th>
        <td><?php 
          print $analysis->program; 
          if($analysis->programversion){
             print " (" . $analysis->programversion . ")"; 
          }
          if($analysis->algorithm){
             print ". " . $analysis->algorithm; 
          }
          ?>
        </td>
      </tr>
      <tr class="tripal_analysis_interpro-table-even-row tripal-table-even-row">
        <th nowrap>Source</th>
        <td><?php 
          if($analysis->sourceuri){
             print "<a href=\"$analysis->sourceuri\">$analysis->sourcename</a>"; 
          } else {
             print $analysis->sourcename; 
          }
          if($analysis->sourceversion){
             print " (" . $analysis->sourceversion . ")"; 
          }
          ?>
          </td>
      </tr>
      <tr class="tripal_analysis_interpro-table-odd-row tripal-table-odd-row">
        <th nowrap>Date performed</th>
        <td><?php print preg_replace("/^(\d+-\d+-\d+) .*/","$1",$analysis->timeexecuted); ?></td>
      </tr>
      <tr class="tripal_analysis_interpro-table-even-row tripal-table-even-row">
        <th nowrap>Description</th>
        <td><?php print $analysis->description; ?></td>
      </tr>      
      <tr class="tripal_analysis_interpro-table-odd-row tripal-table-odd-row">
        <th nowrap>InterProScan Arguments</th>
        <td><?php print $interpro->interproparameters ?></td>
      </tr>        	                                
   </table>   
</div>
