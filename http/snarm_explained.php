<?php
/**
 * @file http/snarm_explained.php
 * @brief Documengtation page explaining how the SNARM ontology works.
 * @version beta
 * @author David R Newman
 * @details Documengtation page explaining how the SNARM ontology works.  Explains how entities in the ontology can be put together to define the policy for using a particular myExperiment Contribution.
 */

	include('include.inc.php');
	$pagetitle="SNARM Explained";
	include('header.inc.php');
?>
The Simple Network Access Rights Management (SNARM) Ontology is a very simple ontology that allows additive policies to be defined to specify who can perform certain actions on particular objects.  It is defined from perspective that those who can perform action are related to each other within a network framework such as a social network with friends and groups...
  
<div align="center">
  <img src="/img/snarm_policy.png" title="myExperiment's Main Entities Diagram"/>
  <br/><br/>
  <p><b>Fig.1</b> The Main Entities of the SNARM Ontology</p>
</div>
<p><b>To Be Finished</b></p>

<?php include('footer.inc.php'); ?>
