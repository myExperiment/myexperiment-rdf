<?php
/**
 * @file inc/config/data.inc.php
 * @brief Defines variables for mapping database recordds to RDF data.
 * @version beta
 * @author David R Newman
 * @details Defines variables for mapping database records to RDF data complient with the description of classes and properties in the myExperiment Ontology.
 */

include_once("config/settings.inc.php");
require_once("functions/utility.inc.php");

/** @brief An associative array mapping RDF properteries to their XML datatypes. */
$datatypes=getDatatypes();

/** @brief An array of MIME types that can have dataflows and their components extracted from them. */
$dataflow_contenttypes=array("application/vnd.taverna.scufl+xml","application/vnd.taverna.t2flow+xml","application/vnd.galaxy.workflow+xml","application/vnd.galaxy.workflow+json","application/vnd.rapidminer.rmp+zip");

/** 
 * @brief A multi-dimensional associative array specifying all the myExperiment entity types a particular pieces of information associated with them including:
 *   - aggregates_resources: A boolean specifying whether the entity aggregates other resources (e.g. A Pack, PackSnapshot or Experiment).
 *   - annotations: An array of strings containing the types of annotations the entity type in question can have. 
 *   - annotation_property: A string representing the name (without namespace) of the RDF property for the annotation entity type in question.
 *   - annotation_where_clause: A string.  If you want to find all annotations (of the entity type in question) for another particular entity what is the where clause required to augment the SQL query.
 *   - db_entity: A string specifying the entity type in the database. (E.g. the entity type in the database of a File is Blob).
 *   - homepage: A boolean specifying whether the entity type in question has an HTML web page representation.
 *   - nested_on: If the entity type can be nested within another entity (e.g. Citations for a WorkflowVersion) what are the database fields in the original entity that are join to this other entity.
 *   - nested_url_subpath_entities: An associative array mapping nested entities URL subpath (e.g. announcements in groups/X/announcemnts/Y) to an entity type.
 *   - no_rdf_uri: A boolean specifying whether the entity type in question has a URI for an RDF representation.
 *   - ontology_prefix:  A string specifying the ontology prefix for the entity type in question.
 *   - versioned_entity: a string specifying the versioned entity of the entity type in question (e.g. for FileVersion this is File).  The reverse of version_entity.
 *   - versioned_id: a string specifying the field in the database table that references the versioned entity of a version.  (E.g. A PackSnapshot's versioned ID in the database is pack_id).
 *   - table: A string specifying The primary database table from which information about the entity can be retrieved.
 *   - url_subpath: The subpath of the URL for an entity, after $datauri but before the ID of the entity (e.g. For http://www.myexperiment.org/workflows/16 the url_subpath is workflows).
 *   - version_entity: A string.  If the entity type is question can be versioned what is the entity type of versions of this entity. (E.g. for File this is FileVersion). The reverse of versioned_entity.  
 *   - xml_service: A string specifying the service that can be called to request XML for the entity type in question.  (E.g. for Citation this is citation, for ContentType this is type).
 */
$entities = array(
	'Announcement' => array("table" => "announcements", "ontology_prefix" => "mebase", "xml_service" => "announcement", "homepage" => true),
	'Attribution' => array("table" => "attributions", "ontology_prefix" => "meac", "nested_on" => array('attributor_type', 'attributor_id')),
	'Citation' => array("table" => "citations", "ontology_prefix" => "meannot", "xml_service" => "citation", "annotation_where_clause" => "(workflow_id='?' and workflow_version='~')", "nested_on" => array('workflow_id','workflow_version'), "annotation_property" => "has-citation"),
	'Comment' => array("table" => "comments", "ontology_prefix" => "meannot", "xml_service" => "comment", "annotation_where_clause" => "(commentable_type='?' and commentable_id='~')", "nested_on" => array('commentable_type','commentable_id'), "annotation_property" => "has-comment"),
	'ContentType' => array("table" => "content_types", "ontology_prefix" => "mebase", "xml_service" => "type", "homepage" => true),
	'Creditation' => array("table"=> "creditations", "ontology_prefix" => "meac", "nested_on" => array('creditable_type','creditable_id')),
	'Experiment' => array("table" => "experiments", "ontology_prefix" => "meexp", "xml_service" => "experiment", "homepage" => true, "aggregates_resources" => true),
	'Favourite' => array("table" => "bookmarks", "ontology_prefix" => "meannot", "xml_service" => "favourite", "annotation_where_clause" => "(bookmarkable_type='?' and bookmarkable_id='~')", "nested_on" => array('user_id'), "annotation_property" => "is-favourited", "url_subpath" => "favourites"),
	'File' => array("table" => "blobs", "version_entity" => "FileVersion", "db_entity" => "Blob", "ontology_prefix" => "mecontrib", "xml_service" => "file", "annotations" => array('Comment','Favourite','Rating','Tagging'), "url_subpath" => "files"),
	'FileVersion' => array("table" => "blob_versions", "versioned_id" => "blob_id", "ontology_prefix" => "mecontrib", "xml_service" => "file", "homepage" => true),
	'Friendship' => array("table" => "friendships", "ontology_prefix" => "mebase", "nested_on" => array('user_id')),
	'Job' => array("table" => "jobs", "ontology_prefix" => "meexp", "xml_service" => "job", "nested_on" => array('experiment_id')),
	'Group' => array("table" => "networks", "db_entity" => "Network", "ontology_prefix" => "mebase", "xml_service" => "group", "homepage" => true, "annotations" => array('GroupAnnouncement', 'Comment','Tagging'), "url_subpath" => "groups", "nested_url_subpath_entities" => array("announcements" => "GroupAnnouncement")),
	'GroupAnnouncement' => array("table" => "group_announcements", "ontology_prefix" => "mebase", "nested_on" => array("announcement_type", "network_id"), "annotation_where_clause" => "network_id='~'", "annotation_property" => "has-announcement"),
	'License' => array("table" => "licenses", "ontology_prefix" => "mebase", "xml_service" => "license", "homepage" => true),
	'LocalPackEntry' => array("table" => "pack_contributable_entries", "db_entity" => "PackContributableEntry", "ontology_prefix" => "mepack", "nested_on" => array('pack_id'), "url_subpath" => "local_pack_entries"),
	'Membership' => array("table" => "memberships", "ontology_prefix" => "mebase"),
	'Message' => array("table" => "messages", "ontology_prefix" => "mebase", "xml_service" => "message", "homepage" => true, "nested_on" => array('user_id')),
	'ObjectProperty' => array("table" => "predicates", "ontology_prefix" => "owl", "no_rdf_uri" => true, "nested_on" => array('ontology_id')),
	'Ontology' => array("table" => "ontologies", "ontology_prefix" => "owl"),
	'Pack' => array("table" => "packs", "version_entity" => "PackSnapshot", "ontology_prefix" => "mepack", "xml_service" => "pack", "homepage" => true, "annotations" => array('Comment','Favourite','Tagging'), "aggregates_resources" => true),
	'PackSnapshot' => array("table" => "pack_versions", "versioned_id" => "pack_id", "ontology_prefix" => "mepack", "xml_service" => "pack", "homepage" => true, "aggregates_resources" => true),
	'Policy' => array("table" => "policies", "ontology_prefix" => "snarm", "nested_on" => array('contributable_type', 'contributable_id')),
	'Rating' => array("table" => "ratings", "ontology_prefix" => "meannot", "xml_service" => "rating", "annotation_where_clause" => "(rateable_type='?' and rateable_id='~')", "nested_on" => array('rateable_type', 'rateable_id'), "annotation_property" => "has-rating"),
	'RelationshipEntry' => array("table" => "relationships", "url_subpath" => "relationship_entries", "ontology_prefix" => "mepack", "nested_on" => array('context_id')),
	'RemotePackEntry' => array("table" => "pack_remote_entries", "db_entity" => "PackRemoteEntry", "ontology_prefix" => "mepack", "nested_on" => array('pack_id'), 'url_subpath' => "remote_pack_entries"),
	'Review' => array("table" => "reviews", "ontology_prefix" => "meannot", "xml_service" => "review", "annotation_where_clause" =>  "(reviewable_type='?' and reviewable_id='~')", "nested_on" => array('reviewable_type', 'reviewable_id'), "annotation_property" => "has-review"),
	'TavernaEnactor' => array("table" => "taverna_enactors", "ontology_prefix" => "mespec", "xml_service" => "runner", "homepage" => true, 'url_subpath' => 'runners' ),
	'Tag' => array("table" => "tags", "ontology_prefix" => "meannot", "xml_service" => "tag", "homepage" => true),
	'Tagging' => array("table" => "taggings", "ontology_prefix" => "meannot", "xml_service" => "tagging", "annotation_where_clause" => "(taggable_type='?' and taggable_id='~')", "nested_on" => array('tag_id'), "annotation_property" => "has-tagging"),
	'User' => array("table" => "users", "ontology_prefix" => "mebase", "xml_service" => "user", "homepage" => true),
	'Workflow' => array("table" => "workflows", "version_entity" => "WorkflowVersion", "ontology_prefix" => "mecontrib", "xml_service" => "workflow", "homepage" => true, "annotations" => array('Comment','Favourite','Rating','Review','Tagging')),
	'WorkflowVersion' => array("table" => "workflow_versions", "versioned_id" => "workflow_id", "ontology_prefix" => "mecontrib", "xml_service" => "workflow", "homepage" => true, "annotations" => array('Citation')),
);

/** @brief An associative array mapping entity types in the database to myExperiment entity types (as defined within the myExperiment ontology). */
$db_entity_mappings = array();
/** @brief An associative array mapping the URL subpath of an entity type to its name (e.g. files maps to File). */
$path_entity_mappings = array();
/** @brief An associative array mapping versioned entity types to their versions (e.g. File maps to FileVersion). */
$versioned_entities = array();
foreach ($entities as $name => $entity) {
	if (empty($entity['url_subpath'])) {
		$entities[$name]['url_subpath'] = $entity['table'];
	}
	$path_entity_mappings[$entities[$name]['url_subpath']] = $name;
	if (empty($entity['db_entity'])) {
		$entities[$name]['db_entity'] = $name;
		$db_entity_mappings[$name] = $name;
	}
	else {
		$db_entity_mappings[$entity['db_entity']] = $name;
	}
	if (!empty($entity['version_entity'])) {
		$entities[$entity['version_entity']]['versioned_entity'] = $name;
		$versioned_entities[$name] = $entity['version_entity'];
	}
}

/** @brief An array of strings listing URL subpaths that entities will not be found under.  (E.g. preview URLs for Workflows). */
$exclude_url_subpaths = array("previews");

/** @brief An asssociative array mapping namespace prefixes to their full URIs. */
$namespace_prefixes = array(
	'snarm' => "{$ontopath}snarm/",
	'mebase' => "{$ontopath}base/",
	'meannot' => "{$ontopath}annotations/",
	'mepack' => "{$ontopath}packs/",
	'meexp' => "{$ontopath}experiments/",
	'mecontrib' => "{$ontopath}contributions/",
	'meac' => "{$ontopath}attrib_credit/",
	'mevd' => "{$ontopath}viewings_downloads/",
	'mecomp' => "{$ontopath}components/",
	'mespec' => "{$ontopath}specific/",
        'myexp' => $datauri,
	'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
        'owl' => 'http://www.w3.org/2002/07/owl#',
        'xsd' => 'http://www.w3.org/2001/XMLSchema#',
        'dc' => 'http://purl.org/dc/elements/1.1/',
        'dcterms' => 'http://purl.org/dc/terms/',
        'foaf' => 'http://xmlns.com/foaf/0.1/',
        'sioc' => 'http://rdfs.org/sioc/ns#',
        'ore' => 'http://www.openarchives.org/ore/terms/',
	'cc' => 'http://creativecommons.org/ns#',
        'skos' => 'http://www.w3.org/2004/02/skos/core#',
        'dbpedia' => 'http://dbpedia.org/ontology/',
);

/** @brief An associative array mapping entity types to SQL queries that can retrieve database field values required to generate RDF for an entity of a particular type.  Depending on whether $domain is set to the string public determines whether the SQL queries will only return records for entity's that are pubically accessable. */
$sql=array();
if (isset($domain) && $domain == "public"){
        $pubcond="policies.share_mode in (0,1,2)";
        $sql['Announcement']="select * from announcements";
        $sql['Attribution']="select attributions.* from attributions inner join contributions on attributions.attributor_id=contributions.contributable_id and attributions.attributor_type=contributions.contributable_type inner join policies on contributions.policy_id=policies.id where ($pubcond)";
        $sql['Comment']="select comments.* from comments left join contributions on comments.commentable_id=contributions.contributable_id and comments.commentable_type=contributions.contributable_type left join policies on contributions.policy_id=policies.id where ($pubcond or comments.commentable_type='Network') and comments.commentable_type in ('Workflow','Pack','Blob','Network')";
        $sql['Citation']="select citations.* from citations inner join contributions on citations.workflow_id=contributions.contributable_id and contributions.contributable_type='Workflow' inner join policies on contributions.policy_id=policies.id where ($pubcond)";
        $sql['ContentType']="select * from content_types";
        $sql['Creditation']="select creditations.* from creditations inner join contributions on creditations.creditable_id=contributions.contributable_id and creditations.creditable_type=contributions.contributable_type inner join policies on contributions.policy_id=policies.id where ($pubcond)";
        $sql['Experiment']="select * from experiments where 1=2";
        $sql['Favourite']="select bookmarks.* from bookmarks inner join contributions on bookmarks.bookmarkable_id=contributions.contributable_id and bookmarks.bookmarkable_type=contributions.contributable_type inner join policies on contributions.policy_id=policies.id where ($pubcond)";
        $sql['File']="select blobs.*, contributions.id as contribution_id, contributions.viewings_count, contributions.downloads_count, policies.id as policy_id, policies.update_mode, policies.share_mode from blobs inner join contributions on contributions.contributable_id=blobs.id inner join policies on contributions.policy_id=policies.id where contributable_type='Blob' and ($pubcond)";
        $sql['FileVersion']="select blob_versions.*, blobs.license_id, contributions.contributor_type, contributions.contributor_id, contributions.id as contribution_id, contributions.viewings_count, contributions.downloads_count, policies.id as policy_id, policies.update_mode, policies.share_mode from blobs inner join contributions on contributions.contributable_id=blobs.id inner join blob_versions on blobs.id=blob_versions.blob_id inner join policies on contributions.policy_id=policies.id where contributable_type='Blob' and ($pubcond)";
        $sql['Friendship']="select * from friendships";
        $sql['Group']="select * from networks";
        $sql['GroupAnnouncement']="select group_announcements.*, 'GroupAnnouncement' as announcement_type from group_announcements where (public=1)";
        $sql['Job']="select jobs.* from jobs inner join contributions on jobs.runnable_id=contributions.contributable_id and jobs.runnable_type=contributions.contributable_type inner join policies on contributions.policy_id=policies.id where ($pubcond and 1=2)";
        $sql['License']="select * from licenses";
        $sql['LocalPackEntry']="select pack_contributable_entries.* from pack_contributable_entries inner join packs on pack_contributable_entries.pack_id=packs.id inner join contributions on packs.id=contributions.contributable_id and contributions.contributable_type='Pack' inner join policies on contributions.policy_id=policies.id where ($pubcond)";
        $sql['Membership']="select * from memberships";
        $sql['Message']="select * from messages where 1=2";
	$sql['ObjectProperty']="select predicates.*, ontologies.uri as ontology_uri from predicates inner join ontologies on predicates.ontology_id=ontologies.id";
        $sql['Ontology']="select * from ontologies";
        $sql['Pack']="select packs.*, contributions.viewings_count, contributions.downloads_count, policies.id as policy_id, policies.update_mode, policies.share_mode from packs inner join contributions on packs.id=contributions.contributable_id and contributions.contributable_type='Pack' inner join policies on contributions.policy_id=policies.id where ($pubcond)";
        $sql['PackSnapshot']="select pack_versions.*, contributions.contributor_type, contributions.contributor_id, contributions.viewings_count, contributions.downloads_count, policies.id as policy_id, policies.update_mode, policies.share_mode from packs inner join contributions on packs.id = contributions.contributable_id and contributions.contributable_type='Pack' inner join pack_versions on packs.id=pack_versions.pack_id inner join policies on contributions.policy_id=policies.id where ($pubcond)";
        $sql['Policy']="select contributions.contributable_type, contributions.contributable_id, contributions.contributor_type, contributions.contributor_id, policies.id as policy_id, policies.id from policies inner join contributions on policies.id=contributions.policy_id where contributable_type in ('Workflow','Pack','Blob','Network') and ($pubcond)";
        $sql['Rating']="select ratings.* from ratings inner join contributions on ratings.rateable_id=contributions.contributable_id and ratings.rateable_type=contributions.contributable_type inner join policies on contributions.policy_id=policies.id where ($pubcond)";
	$sql['RelationshipEntry']="select relationships.*, predicates.title as predicate, ontologies.uri as ontology_uri from relationships inner join predicates on relationships.predicate_id=predicates.id inner join ontologies on predicates.ontology_id=ontologies.id inner join packs on relationships.context_id=packs.id inner join contributions on packs.id=contributions.contributable_id and contributions.contributable_type='Pack' inner join policies on contributions.policy_id=policies.id where context_type='Pack' and ($pubcond)";
        $sql['RemotePackEntry']="select pack_remote_entries.* from pack_remote_entries inner join packs on pack_remote_entries.pack_id=packs.id inner join contributions on packs.id=contributions.contributable_id and contributions.contributable_type='Pack' inner join policies on contributions.policy_id=policies.id where ($pubcond)";
        $sql['Review']="select reviews.* from reviews inner join contributions on reviews.reviewable_id=contributions.contributable_id and reviews.reviewable_type=contributions.contributable_type inner join policies on contributions.policy_id=policies.id where ($pubcond)";
        $sql['TavernaEnactor']="select * from taverna_enactors where 1=2";
        $sql['Tagging']="select taggings.* from taggings left join contributions on taggings.taggable_id=contributions.contributable_id and taggings.taggable_type=contributions.contributable_type left join policies on contributions.policy_id=policies.id where ($pubcond or taggings.taggable_type='Network')";
        $sql['Tag']="select * from tags";
        $sql['User']="select users.*, profiles.picture_id, profiles.email as profile_email, profiles.website, profiles.body_html, profiles.field_or_industry, profiles.occupation_or_roles, profiles.organisations, profiles.location_city, profiles.location_country, profiles.interests, profiles.contact_details, pictures.id as avatar_id from users inner join profiles on users.id=profiles.user_id left join pictures on profiles.picture_id=pictures.id";
        $sql['Workflow']="select workflows.*, contributions.id as contribution_id, contributions.viewings_count, contributions.downloads_count, policies.id as policy_id, policies.update_mode, policies.share_mode from contributions inner join workflows on contributions.contributable_id=workflows.id inner join policies on contributions.policy_id=policies.id where contributable_type='Workflow' and ($pubcond)";
        $sql['WorkflowVersion']="select workflow_versions.*, workflows.license_id, contributions.id as contribution_id, policies.id as policy_id, policies.update_mode, policies.share_mode from contributions inner join workflows on contributions.contributable_id=workflows.id inner join workflow_versions on workflows.id=workflow_versions.workflow_id inner join policies on contributions.policy_id=policies.id where contributable_type='Workflow' and ($pubcond)";
}
else{
        $sql['Announcement']="select announcements.*, users.name from announcements inner join users on announcements.user_id=users.id";
        $sql['Attribution']="select attributions.* from attributions";
        $sql['Comment']="select comments.* from comments where comments.commentable_type in ('Workflow','Pack','Blob','Network')";
        $sql['Citation']="select citations.* from citations";
        $sql['ContentType']="select * from content_types";
        $sql['Creditation']="select creditations.* from creditations";
        $sql['Experiment']="select experiments.* from experiments";
        $sql['Favourite']="select bookmarks.* from bookmarks";
        $sql['File']="select blobs.*, contributions.id as contribution_id, contributions.viewings_count, contributions.downloads_count, policies.id as policy_id, policies.update_mode, policies.share_mode from blobs inner join contributions on contributions.contributable_id=blobs.id inner join policies on contributions.policy_id=policies.id where contributable_type='Blob'";
        $sql['FileVersion']="select blob_versions.*, blobs.license_id, contributions.contributor_type, contributions.contributor_id, contributions.id as contribution_id, contributions.viewings_count, contributions.downloads_count, policies.id as policy_id, policies.update_mode, policies.share_mode from blobs inner join contributions on contributions.contributable_id=blobs.id inner join blob_versions on blobs.id=blob_versions.blob_id inner join policies on contributions.policy_id=policies.id where contributable_type='Blob'";
        $sql['Friendship']="select friendships.* from friendships";
        $sql['Group']="select networks.* from networks";
        $sql['GroupAnnouncement']="select group_announcements.*, 'GroupAnnouncement' as announcement_type from group_announcements";
        $sql['Job']="select jobs.* from jobs";
        $sql['License']="select * from licenses";
        $sql['LocalPackEntry']="select pack_contributable_entries.* from pack_contributable_entries";
        $sql['Membership']="select memberships.* from memberships";
        $sql['Message']="select messages.* from messages";
	$sql['ObjectProperty']="select predicates.*, ontologies.uri as ontology_uri from predicates inner join ontologies on predicates.ontology_id=ontologies.id";
        $sql['Ontology']="select * from ontologies";
        $sql['Pack']="select packs.*, contributions.viewings_count, contributions.downloads_count, policies.id as policy_id, policies.update_mode, policies.share_mode from packs inner join contributions on packs.id = contributions.contributable_id and contributions.contributable_type='Pack' inner join policies on contributions.policy_id=policies.id";
        $sql['PackSnapshot']="select pack_versions.*, contributions.contributor_type, contributions.contributor_id, contributions.viewings_count, contributions.downloads_count, policies.id as policy_id, policies.update_mode, policies.share_mode from packs inner join contributions on packs.id = contributions.contributable_id and contributions.contributable_type='Pack' inner join pack_versions on packs.id=pack_versions.pack_id inner join policies on contributions.policy_id=policies.id";
        $sql['Policy']="select contributions.contributable_type, contributions.contributable_id, contributions.contributor_type, contributions.contributor_id, policies.id as policy_id, policies.id from policies inner join contributions on policies.id=contributions.policy_id where contributable_type in ('Workflow','Pack','Blob','Network')";
        $sql['Rating']="select ratings.* from ratings";
	$sql['RelationshipEntry']="select relationships.*, predicates.title as predicate, ontologies.uri as ontology_uri from relationships inner join predicates on relationships.predicate_id=predicates.id inner join ontologies on predicates.ontology_id=ontologies.id inner join packs on relationships.context_id=packs.id inner join contributions on packs.id=contributions.contributable_id and contributions.contributable_type='Pack' inner join policies on contributions.policy_id=policies.id where context_type='Pack'";
        $sql['RemotePackEntry']="select pack_remote_entries.* from pack_remote_entries";
        $sql['Review']="select reviews.* from reviews";
        $sql['TavernaEnactor']="select taverna_enactors.* from taverna_enactors";
        $sql['Tagging']="select taggings.* from taggings";
        $sql['Tag']="select tags.* from tags";
        $sql['User']="select users.*, profiles.picture_id, profiles.email as profile_email, profiles.website, profiles.body_html, profiles.field_or_industry, profiles.occupation_or_roles, profiles.organisations, profiles.location_city, profiles.location_country, profiles.interests, profiles.contact_details, pictures.id as avatar_id from users inner join profiles on users.id=profiles.user_id left join pictures on profiles.picture_id=pictures.id";
        $sql['Workflow']="select workflows.*, contributions.id as contribution_id, contributions.viewings_count, contributions.downloads_count, policies.id as policy_id, policies.update_mode, policies.share_mode from contributions inner join workflows on contributions.contributable_id=workflows.id inner join policies on contributions.policy_id=policies.id where contributable_type='Workflow'";
        $sql['WorkflowVersion']="select workflow_versions.*, workflows.license_id, contributions.id as contribution_id, policies.id as policy_id, policies.update_mode, policies.share_mode from contributions inner join workflows on contributions.contributable_id=workflows.id inner join workflow_versions on workflows.id=workflow_versions.workflow_id inner join policies on contributions.policy_id=policies.id where contributable_type='Workflow'";
}

/** @brief A multi-dimensional associative array mapping entity types to associative array specifying how database fields should be used to generated RDF properties for an entity.  (See function generateEntityRDF in inc/functions/rdf.inc.php for more information about the syntax used). */
$mappings = array(
	'Announcement' => array('id'=>'url','title'=>'dcterms:title', 'user_id'=>'&User|mebase:has-announcer', 'created_at'=>'dcterms:created', 'updated_at'=>'dcterms:modified', 'body'=>'mebase:text'),
	'Attribution' => array('id'=>'url', 'attributor_type'=>'+attributor_id|meac:attributes', 'attributable_type'=>'+attributable_id|meac:has-attributable', 'created_at'=>'dcterms:created', 'updated_at'=>'dcterms:modified'),
	'Citation' => array('id'=>'url','user_id'=>'&User|mebase:has-annotator','workflow_id'=>'@getVersionURI|mebase:annotates','authors'=>'meannot:authors','title'=>'dcterms:title','publication'=>'meannot:publication','published_at'=>'meannot:published-at','accessed_at'=>'meannot:accessed-at','url'=>'meannot:citation-url','meannot:isbn'=>'isbn','issn'=>'meannot:issn','created_at'=>'dcterms:created', 'updated_at'=>'dcterms:modified'),
	'Comment' => array('id'=>'url','user_id'=>'&User|mebase:has-annotator','commentable_type'=>'+commentable_id|mebase:annotates','title'=>'dcterms:title','comment'=>'mebase:text','created_at'=>'dcterms:created'),
	'ContentType' => array('id'=>'url','user_id'=>'&User|sioc:has_owner','title'=>'dcterms:title','created_at'=>'dcterms:created','updated_at'=>'dcterms:modified','description'=>'dcterms:description','mime_type'=>'dcterms:type'),
	'Creditation' => array('id'=>'url','creditor_type'=>'+creditor_id|meac:credits','creditable_type'=>'+creditable_id|meac:has-creditable','created_at'=>'dcterms:created','updated_at'=>'dcterms:modified'),
	'Experiment' => array('id'=>'url','described_by'=>'@&-getOREDescribedBy|ore:isDescribedBy','title'=>'dcterms:title', 'description'=>'dcterms:description', 'contributor_type'=>'+contributor_id|sioc:has_owner','experiment_manifest'=>'@&getOREAggregatedResources','created_at'=>'dcterms:created', 'updated_at'=>'dcterms:modified','annotations'=>'@&getAnnotations|'),
	'Favourite' => array('id'=>'url','bookmarkable_type'=>'+bookmarkable_id|mebase:annotates','user_id'=>'&User|mebase:has-annotator','title'=>'dcterms:title','created_at'=>'dcterms:created'),
	'File' => array('id'=>'url','content-url'=>'@&-getDownloadURL|mebase:content-url','local_name'=>'mebase:filename','contributor_type'=>'+contributor_id|sioc:has_owner','content_type_id'=>'&ContentType|mebase:has-content-type', 'license_id'=>'&License|cc:license', 'created_at'=>'dcterms:created', 'updated_at'=>'dcterms:modified','title'=>'dcterms:title', 'body'=>'dcterms:description', 'current_version'=>'@&-getCurrentVersion|mebase:has-current-version', 'other_versions'=>'@&-getVersions|', 'viewings_count'=>'mevd:viewed','downloads_count'=>'mevd:downloaded', 'policy_id'=>'@&-getPolicyURI|mebase:has-policy','annotations'=>'@&getAnnotations|'),
	'FileVersion' => array('id'=>'url','content-url'=>'@&-getDownloadURL|mebase:content-url','local_name'=>'mebase:filename','contributor_type'=>'+contributor_id|sioc:has_owner','content_type_id'=>'&ContentType|mebase:has-content-type', 'license_id'=>'&License|cc:license', 'revision_comments'=>'mebase:revision-comments', 'created_at'=>'dcterms:created', 'updated_at'=>'dcterms:modified','title'=>'dcterms:title', 'body'=>'dcterms:description', 'blob_id'=>'&File|dcterms:isVersionOf', 'version'=>'mebase:version-number', 'currentversion'=>'@getIsCurrentVersion|mebase:is-current-version', 'viewings_count'=>'mevd:viewed','downloads_count'=>'mevd:downloaded', 'policy_id'=>'@&-getPolicyURI|mebase:has-policy','annotations'=>'@&getAnnotations|'),
	'Friendship' => array('id'=>'url','user_id'=>'&User|mebase:has-requester','friend_id'=>'&User|mebase:has-accepter','created_at'=>'dcterms:created','accepted_at'=>'mebase:accepted-at','message'=>'mebase:text'),
	'Group' => array('id'=>'url','user_id'=>'&User|sioc:has_owner', 'created_at'=>'dcterms:created', 'updated_at'=>'dcterms:modified','title'=>'sioc:name','description'=>'dcterms:description', 'new_member_policy'=>'mebase:membership-policy','members'=>'@&getMembers|','annotations'=>'@&getAnnotations|'),
	'GroupAnnouncement' => array('id'=>'url','title'=>'dcterms:title','network_id'=>'&Group|mebase:announced-to','user_id'=>'&User|mebase:has-announcer','public'=>'mebase:public-announcement', 'created_at'=>'dcterms:created', 'updated_at'=>'dcterms:modified','body'=>'mebase:text'),
	'Job' => array('id'=>'url','described_by'=>'@&-getOREDescribedBy|ore:isDescribedBy','title'=>'dcterms:title','description'=>'dcterms:description','experiment_id'=>'&Experiment|ore:isAggregatedBy','user_id'=>'&User|sioc:has_owner','runnable'=>'@getRunnable|meexp:has-runnable','runner'=>'@getRunner|meexp:has-runner','submitted_at'=>'meexp:submitted-at','started_at'=>'meexp:started-at','completed_at'=>'meexp:completed-at','last_status'=>'meexp:last-status','last_status_at'=>'meexp:last-status-at','job_uri'=>'mebase:uri','job-manifest'=>'meexp:job-manifest','inputs'=>'@%getInput|meexp:has-input','outputs'=>'@%getOutput|meexp:has-output','created_at'=>'dcterms:created','updated_at'=>'dcterms:modified','parent_job_id'=>'&Job|meexp:has-parent-job'),
	'LocalPackEntry' => array('id'=>'url','pack_id'=>'&Pack|ore:proxyIn','proxy_for'=>'@-getProxyFor|ore:proxyFor','comment'=>'dcterms:description','user_id'=>'&User|sioc:has_owner','created_at'=>'dcterms:created', 'updated_at'=>'dcterms:modified'),
	'License' => array('id'=>'url','unique_name'=>'dcterms:identifier','title'=>'dcterms:title','description'=>'dcterms:description','url'=>'owl:sameAs','user_id'=>'&User|sioc:has_owner','attributes'=>'@getLicenseAttributes|','created_at'=>'dcterms:created','updated_at'=>'dcterms:modified'),
	'Membership' => array('id'=>'url','requester'=>'@getRequester|mebase:has-requester','accepter'=>'@getAccepter|mebase:has-accepter','requested_at'=>'@getRequesterTime|dcterms:created','accepted_at'=>'@getAccepterTime|mebase:accepted-at','message'=>'mebase:text'),
	'Message' => array('id'=>'url','from'=>'&User|mebase:from','to'=>'&User|mebase:to','subject'=>'mebase:subject','body'=>'mebase:text','created_at'=>'dcterms:created','read_at'=>'mebase:read-at','deleted_by_sender'=>'mebase:deleted-by-sender','deleted_by_recepient'=>'mebase:deleted-by-recepient'),
	'ObjectProperty' => array('id'=>'url','title'=>'rdfs:label','description'=>'rdfs:comment','ontology_uri'=>'rdfs:isDefinedBy','','created_at'=>'dcterms:created','updated_at'=>'dcterms:modified'),
	'Ontology' => array('id'=>'url','title'=>'rdfs:label','description'=>'rdfs:comment','user_id'=>'&User|dc:creator','created_at'=>'dcterms:created','updated_at'=>'dcterms:modified','staticdetails'=>'@getStaticOntologyDetails'),
	'Pack' => array('id'=>'url','described_by'=>'@&-getOREDescribedBy|ore:isDescribedBy','manifest'=>'@&getOREAggregatedResources|','entries'=>'@getPackEntries|','contributor_type'=>'+contributor_id|sioc:has_owner','title'=>'dcterms:title', 'description'=>'dcterms:description','created_at'=>'dcterms:created','updated_at'=>'dcterms:modified','current_version'=>'@&-getCurrentVersion|mebase:has-latest-snapshot','other_versions'=>'@&-getVersions|','viewings_count'=>'mevd:viewed','downloads_count'=>'mevd:downloaded','policy_id'=>'@&-getPolicyURI|mebase:has-policy','annotations'=>'@&getAnnotations|'),
	'PackSnapshot' => array('id'=>'url','described_by'=>'@&-getOREDescribedBy|ore:isDescribedBy','manifest'=>'@&getOREAggregatedResources|','entries'=>'@getPackEntries|','contributor_type'=>'+contributor_id|sioc:has_owner','title'=>'dcterms:title', 'description'=>'dcterms:description','pack_id'=>'&Pack|dcterms:isVersionOf','version'=>'mebase:version-number','currentversion'=>'@getIsCurrentVersion|mepack:is-latest-snapshot','revision_comments'=>'mebase:revision-comments','created_at'=>'dcterms:created','updated_at'=>'dcterms:modified','viewings_count'=>'mevd:viewed','downloads_count'=>'mevd:downloaded','policy_id'=>'@&-getPolicyURI|mebase:has-policy','annotations'=>'@&getAnnotations|'),
	'Policy' => array('policy_id'=>'url','policy'=>'@getPolicy|'),
	'Rating' => array('id'=>'url','rateable_type'=>'+rateable_id|mebase:annotates','rating'=>'meannot:rating-score','user_id'=>'&User|mebase:has-annotator','created_at'=>'dcterms:created'),
	'RelationshipEntry' => array('id'=>'url','relationship'=>'@-getRelationship|','context_id'=>'&Pack|ore:proxyIn','user_id'=>'&User|sioc:has_owner','created_at'=>'dcterms:created'),
	'RemotePackEntry' => array('id'=>'url','pack_id'=>'&Pack|ore:proxyIn','title'=>'dcterms:title','proxy_for'=>'@%getProxyFor|ore:proxyFor','alternate_uri'=>'rdfs:seeAlso', 'comment'=>'dcterms:description','user_id'=>'&User|sioc:has_owner', 'created_at'=>'dcterms:created', 'updated_at'=>'dcterms:modified'),
	'Review' => array('id'=>'url','title'=>'dcterms:title','review'=>'mebase:text','reviewable_type'=>'+reviewable_id|mebase:annotates','user_id'=>'&User|mebase:has-annotator','created_at'=>'dcterms:created','updated_at'=>'dcterms:modified'),
	'TavernaEnactor' => array('id'=>'url','title'=>'dcterms:title','description'=>'dcterms:description','contributor_type'=>'+contributor_id|sioc:has_owner','url'=>'meexp:runner-url','username'=>'mebase:username','created_at'=>'dcterms:created','updated_at'=>'dcterms:modified'),
	'Tagging' => array('id'=>'url','tag_id'=>'&Tag|meannot:uses-tag','taggable_type'=>'+taggable_id|mebase:annotates','user_id'=>'&User|mebase:has-annotator','created_at'=>'dcterms:created'),
	'Tag' => array('id'=>'url','name'=>'dcterms:title','created_at'=>'dcterms:created','updated_at'=>'dcterms:modified','taggings'=>'@getTaggings|'),
	'User' => array('id'=>'url','created_at'=>'dcterms:created','updated_at'=>'dcterms:modified','name'=>'@-getSIOCAndFOAFName|','body'=>'dcterms:description','avatar_id'=>'@-getPictureURL|sioc:avatar','location_city'=>'<foaf:based_near', 'location_country'=>'<mebase:country','residence'=>'@&getResidence|','website'=>'<foaf:homepage', 'last_seen_at'=>'mebase:last-seen-at','activated_at'=>'mebase:activated-at','receive_notifications'=>'mebase:receive-notifications','profile_email'=>'@-getMailtoProfile|foaf:mbox','email_sha1sum'=>'@-getMboxSHA1Sum|foaf:mbox_sha1sum','organisation'=>'mebase:organisation','field_or_industry'=>'mebase:field','occupations_or_roles'=>'mebase:occuptation','interests'=>'mebase:interests','contact_details'=>'mebase:contact-details','friendships'=>'@getFriendships|','memberships'=>'@getMemberships|','favourites'=>'@getFavourites|'),
	'Workflow' => array('id'=>'url','title'=>'dcterms:title','body'=>'dcterms:description', 'content_type_id'=>'&ContentType|mebase:has-content-type', 'contributor_type'=>'+contributor_id|sioc:has_owner','created_at'=>'dcterms:created','updated_at'=>'dcterms:modified','filename'=>'@&-getFilename|mebase:filename','content-url'=>'@&-getDownloadURL|mebase:content-url','preview'=>'@-getPreview|mecontrib:preview','thumbnail'=>'@-getThumbnail|mecontrib:thumbnail', 'thumbnail_big'=>'@-getThumbnailBig|mecontrib:thumbnail-big','svg'=>'@-getSVG|mecontrib:svg','current_version'=>'@&-getCurrentVersion|mebase:has-current-version','other_versions'=>'@&-getVersions|','license_id'=>'&License|cc:license','last_edited_by'=>'&User|mebase:last-edited-by','viewings_count'=>'mevd:viewed','downloads_count'=>'mevd:downloaded','policy_id'=>'@&-getPolicyURI|mebase:has-policy','dataflow'=>'@&-getDataflow|mecomp:executes-dataflow','annotations'=>'@&getAnnotations|'),
	'WorkflowVersion' => array('id'=>'url','title'=>'dcterms:title','body'=>'dcterms:description','content_type_id'=>'&ContentType|mebase:has-content-type', 'workflow_id'=>'&Workflow|dcterms:isVersionOf','version'=>'mebase:version-number','currentversion'=>'@getIsCurrentVersion|mebase:is-current-version','revision_comments'=>'mebase:revision-comments','contributor_type'=>'+contributor_id|sioc:has_owner', 'created_at'=>'dcterms:created', 'updated_at'=>'dcterms:modified','filename'=>'@&-getFilename|mebase:filename','content-url'=>'@&-getDownloadURL|mebase:content-url','preview'=>'@-getPreview|mecontrib:preview', 'thumbnail'=>'@-getThumbnail|mecontrib:thumbnail', 'thumbnail_big'=>'@-getThumbnailBig|mecontrib:thumbnail-big', 'svg'=>'@-getSVG|mecontrib:svg','license_id'=>'&License|cc:license','last_edited_by'=>'&User|mebase:last-edited-by','policy_id'=>'@&-getPolicyURI|mebase:has-policy','dataflow'=>'@&-getDataflow|mecomp:executes-dataflow','annotations'=>'@&getAnnotations|'),
);
