<?php

define('CCMONITOR_VERSION', 8);

include('configuration/conf.php');

// You can provide another monitor instance set using instance GET param
if (!empty($_GET['instance']))
	$instance = $_GET['instance'];
else
	$instance = reset(array_keys($instances));

if (!empty($_GET['fetch_data']))
{
	try
	{
		include_once('includes/provider.php');
		$return = array(
			'result' => 'ok',
			'data'   => CcProvider::getInstance($_GET['fetch_data'])->fetchData(),
			'version' => CCMONITOR_VERSION,
		);
	}
	catch (Exception $e)
	{
		$return = array(
			'result'  => 'error',
			'message' => $e->getMessage()
		);
	}

	header('Content-type: application/json; charset=utf-8');
	header('Cache-Control: no-cache');
	echo json_encode($return);
	exit();
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en" xml:lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<link href="css/ccmonitor.css?v=<?php echo CCMONITOR_VERSION ?>" rel="stylesheet" type="text/css" media="screen, projection" />
		<title>Cruise Control monitor</title>
	</head>
	<body>
		<div id="content"></div>
		<script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
		<script type="text/javascript" src="js/ccmonitor.js?v=<?php echo CCMONITOR_VERSION ?>"></script>
		<script type="text/javascript">
			ccInstance = <?php echo json_encode($instance); ?>;
			$(function() {
				fetchData();
				$(window).resize(function() {
					renderMonitor(ccData, ccInfo);
				});
			});
		</script>
	</body>
</html>