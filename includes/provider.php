<?php

/**
 * Cruise Control data provider
 */
class CcProvider
{
	/**
	 * provider URL
	 * @var string
	 */
	protected $url;

	/**
	 * Projects to be included
	 * @var array
	 */
	protected $includedProjects = array();

	/**
	 * Projects to be excluded
	 * @var array
	 */
	protected $excludedProjects = array();

	/**
	 * Optional parameters
	 * @var array
	 */
	protected $options = array();

	/**
	 *
	 * @param type $url
	 * @param type $includedProjects
	 * @param type $excludedProjects
	 * @param type $options
	 */
	public function __construct($url, $includedProjects = array(), $excludedProjects = array(), $options = array())
	{
		$this->url = $url;
		$this->includedProjects = $includedProjects;
		$this->excludedProjects = $excludedProjects;
		$this->options = $options;
	}

	/**
	 * Return the instance singleton from config
	 * @global array $instances
	 * @param string $instanceName
	 * @return CcProvider
	 */
	public static function getInstance($instanceName)
	{
		include(dirname(dirname(str_replace('\\', '/', __FILE__))) . '/configuration/conf.php');

		if (empty($instances[$instanceName]))
			throw new Exception("Instance $instanceName does not exist.");

		if (empty($instances[$instanceName]['type']) || empty($instances[$instanceName]['url']))
			throw new Exception("No URL or type set for instance $instanceName.");

		if (!class_exists($instances[$instanceName]['type']))
			throw new Exception("Type " . $instances[$instanceName]['type'] . " does not exist for instance $instanceName.");

		if (!empty($instances[$instanceName]['include']))
			$includedProjects = $instances[$instanceName]['include'];
		else
			$includedProjects = array();

		if (!empty($instances[$instanceName]['exclude']))
			$excludedProjects = $instances[$instanceName]['exclude'];
		else
			$excludedProjects = array();

		$options = array();
		if (!empty($instances[$instanceName]['projectDisplayName']))
			$options['projectDisplayName'] = $instances[$instanceName]['projectDisplayName'];

		return new $instances[$instanceName]['type']($instances[$instanceName]['url'], $includedProjects, $excludedProjects, $options);
	}

	/**
	 * Fetch data from server
	 * @return array
	 */
	public function fetchData()
	{
		set_time_limit(0);

		$userPictureDir = dirname(dirname(str_replace('\\', '/', __FILE__))) . '/user_pictures/';

		$projectsXml = new DOMDocument();
		@$projectsXml->load($this->url . 'dashboard/cctray.xml');

		$projectNodes = $projectsXml->getElementsByTagName("Project");
		$projects = array();
		foreach($projectNodes as $aProjectNode)
		{
			// Include this project
			if (!empty($this->includedProjects) && !in_array($aProjectNode->getAttribute('name'), $this->includedProjects))
				continue;

			// Exclude this project
			if (!empty($this->excludedProjects) && in_array($aProjectNode->getAttribute('name'), $this->excludedProjects))
				continue;

			$lastBuildDate = new DateTime($aProjectNode->getAttribute('lastBuildTime'));
			$timeAgo = time() - $lastBuildDate->format('U');

			$buildLabel = $aProjectNode->getAttribute('lastBuildLabel');
			if(!empty($buildLabel))
				$buildLabel = 'L'. $buildLabel;

			$buildXml = new DOMDocument();
			@$buildXml->load($this->url . 'cruisecontrol/logs/' . $aProjectNode->getAttribute('name') . '/log' . $lastBuildDate->format('YmdHis') . $buildLabel);
			$buildXPath = new DOMXPath($buildXml);

			// Get the last modification
			$modificationNodes = $buildXml->getElementsByTagName("modification");
			if ($modificationNodes->length)
			{
				$lastModificationDate = new DateTime($modificationNodes->item(0)->getElementsByTagName('date')->item(0)->textContent);
				$modifiedTimeAgo = time() - $lastModificationDate->format('U');

				// Grab user name and picture
				$user = $modificationNodes->item(0)->getElementsByTagName('user')->item(0)->textContent;

				// 1. Picture name is username.jpg
				$userPicture = $user;
				if (file_exists($userPictureDir . $userPicture . '.jpg'))
					$userPicture .= '.jpg?v=' . base64_encode(md5_file($userPictureDir . $userPicture . '.jpg', true));
				else
				{
					// 2. Picture name is lowercase email, without domain part
					$userPicture = strtolower(preg_replace('/@.+$/', '', $user));
					if (file_exists($userPictureDir . $userPicture . '.jpg'))
						$userPicture .= '.jpg?v=' . base64_encode(md5_file($userPictureDir . $userPicture . '.jpg', true));
					else
					{
						// 3. Picture name is the first word of the username
						$userPicture = strtolower(preg_replace('/[^a-z0-9].+$/', '', $userPicture));
						if (file_exists($userPictureDir . $userPicture . '.jpg'))
							$userPicture .= '.jpg?v=' . base64_encode(md5_file($userPictureDir . $userPicture . '.jpg', true));
						else
							$userPicture = null;
					}
				}

				// Get revision
				if ($modificationNodes->item(0)->getElementsByTagName('revision')->length)
					$revision = $modificationNodes->item(0)->getElementsByTagName('revision')->item(0)->textContent;
				else
					$revision = null;

				$modification = array(
					'date'        => $lastModificationDate->format('d/m/Y – H:i:s'),
					'user'        => $user,
					'userPicture' => $userPicture,
					'comment'     => nl2br(htmlspecialchars(str_replace("\r", '', $modificationNodes->item(0)->getElementsByTagName('comment')->item(0)->textContent))),
					'revision'    => $revision,
				);
			}
			else
				$modification = array();

			// Getting failed tests
			$failedTests = array();
			$totalAssertions = 0;
			$totalTests      = 0;
			$totalFailures   = 0;
			$totalErrors     = 0;
			$totalTime       = 0;
			$testSuiteNodes = $buildXPath->query('//testsuites/testsuite');
			foreach($testSuiteNodes as $aTestSuiteNode)
			{
				$totalTests      += $tests      = $aTestSuiteNode->getAttribute('tests');
				$totalAssertions += $assertions = $aTestSuiteNode->getAttribute('assertions');
				$totalFailures   += $failures   = $aTestSuiteNode->getAttribute('failures');
				$totalErrors     += $errors     = $aTestSuiteNode->getAttribute('errors');
				$totalTime       += $totalTime  = $aTestSuiteNode->getAttribute('time');

				if ($failures > 0 || $errors > 0)
					$failedTests[] = array(
						'name'       => $aTestSuiteNode->getAttribute('name'),
						'tests'      => (int) $aTestSuiteNode->getAttribute('tests'),
						'failures'   => (int) $aTestSuiteNode->getAttribute('failures'),
						'errors'     => (int) $aTestSuiteNode->getAttribute('errors'),
						'assertions' => (int) $aTestSuiteNode->getAttribute('assertions')
					);
			}

			// Get total time (human readable)
			$strTotalTime = null;
			$buildNodes = $buildXml->getElementsByTagName("build");
			if ($buildNodes->length > 0)
				$strTotalTime = $buildNodes->item(0)->getAttribute('time');

			$projectName = $aProjectNode->getAttribute('name');

			if (!empty($this->options['projectDisplayName'][$projectName]))
				$projectDisplayName = ' ' . $this->options['projectDisplayName'][$projectName];
			else
				$projectDisplayName = $projectName;

			$projects[$projectName] = array(
				'name'            => $projectDisplayName,
				'activity'        => strtolower($aProjectNode->getAttribute('activity')),
				'lastBuildStatus' => $aProjectNode->getAttribute('lastBuildStatus'),
				'lastBuildLabel'  => $aProjectNode->getAttribute('lastBuildLabel'),
				'lastBuildTime'   => $lastBuildDate->format('d/m/Y – H:i:s'),
				'timeAgo'         => $timeAgo,
				'strTimeAgo'      => self::ago($timeAgo),
				'url'             => $aProjectNode->getAttribute('webUrl'),
				'modification'    => $modification,
				'failedTests'     => $failedTests,
				'tests'           => $totalTests,
				'assertions'      => $totalAssertions,
				'failures'        => $totalFailures,
				'errors'          => $totalErrors,
				'strTotalTime'    => $strTotalTime,
			);
		}

		// Sort projects by name
		uasort($projects, array('CcProvider', 'compareProjects'));

		// Reorder projects using order set in configuration file
		if (!empty($this->options['projectDisplayName']))
		{
			$projects2 = array();
			foreach(array_keys($this->options['projectDisplayName']) as $projectName)
			{
				$projects2[$projectName] = $projects[$projectName];
				unset($projects[$projectName]);
			}

			$projects = array_merge($projects2, $projects);
		}

		return array_values($projects);
	}

	/**
	 * Compare 2 project arrays
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	public static function compareProjects($a, $b)
	{
		if ($a['name'] < $b['name'])
			return -1;
		else if ($a['name'] > $b['name'])
			return 1;
		else
			return 0;
	}

	/**
	 * Return "time ago" string from duration
	 * @param integer $duration
	 * @return string
	 */
	public static function ago($duration)
	{
		$suffix = " ago";

		$years = round($duration / (24 * 60 * 60 * 365));
		if ($years > 0)
			return self::pluralize($years, "year") . $suffix;

		$months = round($duration / (24 * 60 * 60 * 31));
		if ($months > 0)
			return self::pluralize($months, "month") . $suffix;

		$days = round($duration / (24 * 60 * 60));
		if ($days > 1)
			return self::pluralize($days, "day") . $suffix;

		$hours = round($duration / (60 * 60));
		if ($hours > 0)
			return self::pluralize($hours, "hour") . $suffix;

		$minutes = round($duration / (60));
		if ($minutes > 0)
			return self::pluralize($minutes, "minute") . $suffix;
	}

	/**
	 * Pluralize the provided text according to the given amount
	 * @param integer $count
	 * @param string  $text
	 * @return string
	 */
	public static function pluralize($count, $text)
	{
		return $count . (($count == 1) ? (" $text") : (" ${text}s"));
	}
}