<?php
/**
 * @file http/hts/1.php
 * @brief How to SPARQL guide: 1. Using the SPARQL Endpoint
 * @version beta
 * @author David R Newman
 * @details First page of the How to SPARQL guide explaining how to use the myExperiment SPARQL endpoint web interface.
 */
?>

<h2>1. Using the SPARQL Endpoint</h2>
<h3><a name="Useful Prefixes"/>1.1. Useful Prefixes</h3>
<p>The <a href="/sparql">myExperiment SPARQL endpoint</a> has a number of features to assist in its use.  As explained in the <a href="?page=PREFIX">PREFIX</a> page, both the PREFIX and BASE clauses facilitate writing more succinct and easier to follow queries.  If you need to include any prefixes in your query just use the PREFIX as defined in Useful Prefixes (e.g. rdfs, mebase etc.) and the PREFIX line will be prepended to the query before it is executed.</p>
<h3><a name="Formatting"/>1.2. Formatting</h3>
<p>myExperiment's triplestore to which the SPARQL endpoint queries is updated with the previous day's data between 08:10 and 08:30 each morning UK time.  The endpoint provides information about the time the latest snapshot was taken and the number of triples, so you can be sure how current the data is.</p>
<p>SPARQL results can be rendered in a number of formats:</p>
<ul>
  <li><b>HTML Table:</b> Renders the results in an HTML table, giving a more visual way to view your results.</li>
  <li><b>XML:</b> Renders just the SPARQL results on an application/sparql-results+xml content type page.</li>
  <li><b>Text:</b> Returns a Simple Subject-Predicate-Object text representation.</li>
  <li><b>JSON:</b> Returns a JSON encoded version, of SPARQL results XML.</li>
  <li><b>CSV:</b> Returns results as comma separated values, in table columns format.</li>
  <li><b>CSV Matrix:</b> Returns results as comma separated values in a matrix format, where the first variable is enumrated on the x-axis, the second variable is enumerated on the y-axis and 1s are rendered for each tuplet.</li>
</ul>
<p>An example of a use for the CSV Matrix format is for friendships:</p>
<div class="yellow"><pre>PREFIX mebase: &lt;http://rdf.myexperiment.org/ontologies/base/&gt;
PREFIX rdf: &lt;http://www.w3.org/1999/02/22-rdf-syntax-ns#&gt;
SELECT ?requester ?accepter
WHERE{
  ?friendship rdf:type mebase:Friendship ;
    mebase:accepted-at ?accepted_time ;
    mebase:has-requester ?requester ;
    mebase:has-accepter ?accepter .
}</pre><div style="text-align: right; float: right; position: relative; top: -35px;">[<a href="/sparql?query=PREFIX+mebase%3A+%3Chttp%3A%2F%2Frdf.myexperiment.org%2Fontologies%2Fbase%2F%3E%0D%0APREFIX+rdf%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23%3E%0D%0ASELECT+%3Frequester+%3Faccepter+%0D%0AWHERE%7B+%0D%0A++%3Ffriendship+rdf%3Atype+mebase%3AFriendship+%3B%0D%0A++++mebase%3Aaccepted-at+%3Faccepted_time+%3B+%0D%0A++++mebase%3Ahas-requester+%3Frequester+%3B%0D%0A++++mebase%3Ahas-accepter+%3Faccepter+%0D%0A%7D&amp;formatting=CSV Matrix">Run</a>]<br/><span id="results1_show" onclick="showResults('results1');" style="display: none;">[<span class="link">Show&nbsp;Example&nbsp;Results</span>]</span><span id="results1_hide" onclick="hideResults('results1');">[<span class="link">Hide&nbsp;Example&nbsp;Results</span>]</span></div></div>
<div class="green" id="results1" style="clear: both; position: relative; top: -26px; text-align: center;">
  <img src="img/fs_results.png" alt="Friendship CVS Matrix Results" />
</div>
<br/>
<h3><a name="Soft Limit"/>1.3. Soft Limit</h3>
<p>The <em>Soft Limit</em> option determines the amount of resources dedicated to returning all the matching results.  In general 1% is sufficient.  However, if all the results are not returned then a warning message will be displayed and you can try re-running the query with a greater Soft Limit percentage.</p>
<h3><a name="Reasoning"/>1.4. Reasoning</h3> 
<p>The <em>Enable RDFS Reasoning</em> option allows you to make use of <a href="http://4s-reasoner.ecs.soton.ac.uk/">4Store Reasoner</a>, an RDFS reasoner addition to 4Store.  This will perform query-time RDFS reasoning on RDFS subClassOf, subPropertyOf, domain and range properties.  The following query will return more results if RDFS reasoning is enabled because all the super classes of Workflow:</p>
<div class="yellow"><pre>PREFIX rdf: &lt;http://www.w3.org/1999/02/22-rdf-syntax-ns#&gt;
SELECT DISTINCT ?type 
WHERE { 
  &lt;http://www.myexperiment.org/workflows/16&gt; rdf:type ?type 
}</pre><div style="float: right; position: relative; top: -35px; text-align: right;">[<a href="/sparql?query=PREFIX+rdf%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23%3E%0D%0ASELECT+DISTINCT+%3Ftype+%0D%0AWHERE+%7B+%0D%0A++%3C<?= urlencode($datauri) ?>workflows%2F16%3E+rdf%3Atype+%3Ftype+%0D%0A%7D%0D%0A%0D%0A&amp;formatting=HTML Table">Run <font style="font-size: 0.6em;">(Without Reasoning)</font></a>]
[<a href="/sparql?query=PREFIX+rdf%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23%3E%0D%0ASELECT+DISTINCT+%3Ftype+%0D%0AWHERE+%7B+%0D%0A++%3C<?= urlencode($datauri) ?>workflows%2F16%3E+rdf%3Atype+%3Ftype+%0D%0A%7D%0D%0A%0D%0A&amp;formatting=HTML Table&amp;reasoning=1">Run <font style="font-size: 0.6em;">(With Reasoning)</font></a>]<br/><span id="results2_show" onclick="showResults('results2');" style="display: none;">[<span class="link">Show&nbsp;Example&nbsp;Results</span>]</span><span id="results2_hide" onclick="hideResults('results2');">[<span class="link">Hide&nbsp;Example&nbsp;Results</span>]</span></div></div>
<div class="green" id="results2" style="clear: both; position: relative; top: -26px;">
  <table style="margin-left: auto; margin-right: auto;"><tr><td style="vertical-align: top;">
    <h4 style="text-align: center; padding-bottom: 5px;">Without Reasoning</h4>
    <table class="listing">
      <tr><th>type</th></tr>
      <tr><td class="shade">http://rdf.myexperiment.org/ontologies/contributions/Workflow</td></tr>
    </table>
  </td>
  <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
  <td style="vertical-align: top;">
    <h4 style="text-align: center; padding-bottom: 5px;">With Reasoning</h4>
    <table class="listing">
      <tr><th>type</th></tr>
      <tr><td class="shade">b139c71020000004f</td></tr>
      <tr><td>http://rdf.myexperiment.org/ontologies/base/Submission</td></tr>
      <tr><td class="shade">http://rdf.myexperiment.org/ontologies/attrib_credit/Attributable</td></tr>
      <tr><td>http://rdf.myexperiment.org/ontologies/annotations/Favouritable</td></tr>
      <tr><td class="shade">http://rdf.myexperiment.org/ontologies/annotations/Commentable</td></tr>
      <tr><td>http://rdf.myexperiment.org/ontologies/attrib_credit/Creditable</td></tr>
      <tr><td class="shade">http://rdf.myexperiment.org/ontologies/base/Upload</td></tr>
      <tr><td>http://rdf.myexperiment.org/ontologies/annotations/Taggable</td></tr>
      <tr><td class="shade">http://rdf.myexperiment.org/ontologies/annotations/Citationable</td></tr>
      <tr><td>http://rdf.myexperiment.org/ontologies/contributions/Workflow</td></tr>
      <tr><td class="shade">http://rdf.myexperiment.org/ontologies/annotations/Rateable</td></tr>
      <tr><td>http://rdf.myexperiment.org/ontologies/base/Annotatable</td></tr>
      <tr><td class="shade">http://rdf.myexperiment.org/ontologies/base/Contribution</td></tr>
      <tr><td>http://rdf.myexperiment.org/ontologies/base/Interface</td></tr>
      <tr><td class="shade">http://rdf.myexperiment.org/ontologies/base/Versionable</td></tr>
      <tr><td>http://rdf.myexperiment.org/ontologies/contributions/AbstractWorkflow</td></tr>
      <tr><td class="shade">http://rdf.myexperiment.org/ontologies/experiments/Runnable</td></tr>
      <tr><td>http://rdf.myexperiment.org/ontologies/annotations/Reviewable</td></tr>
    </table>
  </td></tr></table>
</div>

<h3><a name="Automated Querying"/>1.5. Automated Querying</h3>
<p>If you wish to write automated queries rather than using the endpoint form you can insert the query (in URL encoded format) into the URL as the <em>query</em> parameter in the HTTP GET header.  If you have built a query using the endpoint form and want to use it as an automated service in something such as a workflow, instead of clicking &quot;Submit Query&quot;, click on &quot;Generate Service from Query&quot;.  This will take you to a page with a link something like the one below, that you can copy and paste into your workflow or HTTP request capable application.</a></p>
<code><small><a target="_blank" href="/sparql?query=PREFIX+mebase%3A+%3Chttp%3A%2F%2Frdf.myexperiment.org%2Fontologies%2Fbase%2F%3E%0D%0APREFIX+rdf%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23%3E%0D%0ASELECT+%3Frequester+%3Faccepter+%0D%0AWHERE%7B+%0D%0A++%3Ffriendship+rdf%3Atype+mebase%3AFriendship+%3B%0D%0A++++mebase%3Aaccepted-at+%3Faccepted_time+%3B+%0D%0A++++mebase%3Ahas-requester+%3Frequester+%3B%0D%0A++++mebase%3Ahas-accepter+%3Faccepter+%0D%0A%7D"><?= $hostpath ?>sparql<b>?query=PREFIX+mebase%3A+%3Chttp%3A%2F%2Frdf.myexperiment.org%2Fontologies%2Fbase%2F%3E%0D%0APREFIX+rdf%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23%3E%0D%0ASELECT+%3Frequester+%3Faccepter+%0D%0AWHERE%7B+%0D%0A++%3Ffriendship+rdf%3Atype+mebase%3AFriendship+%3B%0D%0A++++mebase%3Aaccepted-at+%3Faccepted_time+%3B+%0D%0A++++mebase%3Ahas-requester+%3Frequester+%3B%0D%0A++++mebase%3Ahas-accepter+%3Faccepter+%0D%0A%7D</b></a></small></code>
<br/><br/>
<p>As you will notice is you click on the link above this will return results is raw SPARQL results XML format.  If you wish to get the results in a different format you can also set the <em>formatting</em> parameter in the GET header:</p>
<code><small><a target="_blank" href="/sparql?query=PREFIX+mebase%3A+%3Chttp%3A%2F%2Frdf.myexperiment.org%2Fontologies%2Fbase%2F%3E%0D%0APREFIX+rdf%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23%3E%0D%0ASELECT+%3Frequester+%3Faccepter+%0D%0AWHERE%7B+%0D%0A++%3Ffriendship+rdf%3Atype+mebase%3AFriendship+%3B%0D%0A++++mebase%3Aaccepted-at+%3Faccepted_time+%3B+%0D%0A++++mebase%3Ahas-requester+%3Frequester+%3B%0D%0A++++mebase%3Ahas-accepter+%3Faccepter+%0D%0A%7D&amp;formatting=In Page"><?= $hostpath ?>sparql?query=PREFIX+mebase%3A+%3Chttp%3A%2F%2Frdf.myexperiment.org%2Fontologies%2Fbase%2F%3E%0D%0APREFIX+rdf%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23%3E%0D%0ASELECT+%3Frequester+%3Faccepter+%0D%0AWHERE%7B+%0D%0A++%3Ffriendship+rdf%3Atype+mebase%3AFriendship+%3B%0D%0A++++mebase%3Aaccepted-at+%3Faccepted_time+%3B+%0D%0A++++mebase%3Ahas-requester+%3Frequester+%3B%0D%0A++++mebase%3Ahas-accepter+%3Faccepter+%0D%0A%7D<b>&amp;formatting=HTML Table</b></a></small></code>
<p>The options for the formatting parameter are:</p>
<ul>
  <li>HTML Table</li>
  <li>XML</li>
  <li>Text</li>
  <li>JSON</li>
  <li>CSV</li>
  <li>CSV Matrix</li>
</ul>
<p>The Soft Limit can also be set as an integer between 1 (default) and 100 by using the GET header parameter <em>softlimit</em>:</p>
<code><small><a target="_blank" href="/sparql?query=PREFIX+mebase%3A+%3Chttp%3A%2F%2Frdf.myexperiment.org%2Fontologies%2Fbase%2F%3E%0D%0APREFIX+rdf%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23%3E%0D%0ASELECT+%3Frequester+%3Faccepter+%0D%0AWHERE%7B+%0D%0A++%3Ffriendship+rdf%3Atype+mebase%3AFriendship+%3B%0D%0A++++mebase%3Aaccepted-at+%3Faccepted_time+%3B+%0D%0A++++mebase%3Ahas-requester+%3Frequester+%3B%0D%0A++++mebase%3Ahas-accepter+%3Faccepter+%0D%0A%7D&amp;formatting=In Page&amp;softlimit=5"><?= $hostpath ?>sparql?query=PREFIX+mebase%3A+%3Chttp%3A%2F%2Frdf.myexperiment.org%2Fontologies%2Fbase%2F%3E%0D%0APREFIX+rdf%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23%3E%0D%0ASELECT+%3Frequester+%3Faccepter+%0D%0AWHERE%7B+%0D%0A++%3Ffriendship+rdf%3Atype+mebase%3AFriendship+%3B%0D%0A++++mebase%3Aaccepted-at+%3Faccepted_time+%3B+%0D%0A++++mebase%3Ahas-requester+%3Frequester+%3B%0D%0A++++mebase%3Ahas-accepter+%3Faccepter+%0D%0A%7D&amp;formatting=HTML Table<b>&amp;softlimit=5</b></a></small></code>
<br/><br/>
<p>Finally reasoning can be enabled by setting the <em>reasoning</em> parameter to 1, yes, or true in the GET header:</p>
<code><small><a target="_blank" href="/sparql?query=PREFIX+mebase%3A+%3Chttp%3A%2F%2Frdf.myexperiment.org%2Fontologies%2Fbase%2F%3E%0D%0APREFIX+rdf%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23%3E%0D%0ASELECT+%3Frequester+%3Faccepter+%0D%0AWHERE%7B+%0D%0A++%3Ffriendship+rdf%3Atype+mebase%3AFriendship+%3B%0D%0A++++mebase%3Aaccepted-at+%3Faccepted_time+%3B+%0D%0A++++mebase%3Ahas-requester+%3Frequester+%3B%0D%0A++++mebase%3Ahas-accepter+%3Faccepter+%0D%0A%7D&amp;formatting=In Page&amp;reasoning=1"><?= $hostpath ?>sparql?query=PREFIX+mebase%3A+%3Chttp%3A%2F%2Frdf.myexperiment.org%2Fontologies%2Fbase%2F%3E%0D%0APREFIX+rdf%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23%3E%0D%0ASELECT+%3Frequester+%3Faccepter+%0D%0AWHERE%7B+%0D%0A++%3Ffriendship+rdf%3Atype+mebase%3AFriendship+%3B%0D%0A++++mebase%3Aaccepted-at+%3Faccepted_time+%3B+%0D%0A++++mebase%3Ahas-requester+%3Frequester+%3B%0D%0A++++mebase%3Ahas-accepter+%3Faccepter+%0D%0A%7D&amp;formatting=HTML Table<b>&amp;reasoning=1</b></a></small></code>

<br/><br/>

<script type= "text/javascript"><!-- 
  hideResults('results1');
  hideResults('results2')
--></script>

