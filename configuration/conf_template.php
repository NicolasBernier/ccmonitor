<?php
// Display names for the projects. This is optional. If not set, the name used
// in CruiseControl config file will be used.
// Key: Name in CruiseControl config file.
// Value: Screen name
$projectDisplayNames = array(
	'my_project'          => 'My Project RC 1.2.0.1',
	'my_project.trunk'    => 'My Project trunk',
	'my_other_project.v2' => 'My Other Project RC 2.0.3.4',
);

// Monitor instance sets. The first one will be used by default
$instances = array(
	// Release candidate branches
	'rc' => array(
		'type'               => 'CcProvider',                                        // Provider classname
		'url'                => 'http://user:pass@cruisecontrol.myserver.com:8080/', // CruiseControl dashboard URL. Don't forget the trailing slash!
		'exclude'            => array('my_project.trunk'),                           // Excluded projects (all projects but these ones)
		'projectDisplayName' => $projectDisplayNames                                 // Project display name overrides
	),
	// Trunk branches
	'trunk' => array(
		'type'               => 'CcProvider',
		'url'                => 'http://user:pass@cruisecontrol.myserver.com:8080/',
		'include'            => array('my_project.trunk', 'my_other_project.trunk'), // Included projects (these projects only)
		'projectDisplayName' => $projectDisplayNames
	),
	// All
	'all' => array(
		'type'               => 'CcProvider',
		'url'                => 'http://user:pass@cruisecontrol.myserver.com:8080/',
		'projectDisplayName' => $projectDisplayNames
	),
);