var ccInstance = '';
var ccData = [];
var ccInfo = [];
var ccVersion = 0;

function fetchData()
{
	setTimeout(fetchData, 30000);

	$.ajax({
		url:'index.php',
		data: {fetch_data: ccInstance},
		success: function(data) {
			if (!data || typeof data.result == 'undefined')
				return;

			if (data.result == 'error')
			{
				alert(data.message);
				return;
			}

			renderMonitor(data.data, data.info);
			ccData = data.data;
			ccInfo = data.info;

			if (ccVersion != 0 && ccVersion != data.version)
				window.location.reload();
			ccVersion = data.version;
		}
	});
}

function renderMonitor(data, info)
{
	if (data.length == 0)
		return;

	var rowHeight = $(window).height() / data.length - 2;
	var rowWidth  = $(window).width();

	// Resize viewport
	$('#content').css({overflow: 'hidden'});
	$('#content').width($(window).width());
	$('#content').height($(window).height());

	if (data.length != $('#content div.block').length)
		$('#content div.block').remove();

	$(data).each(function(rowId, rowData) {
		// Create project block
		var block = $('<div id="row_' + rowId + '" class="block"></div>');

		block.height(rowHeight);

		// Icon
		var icon = $('<img />');
		icon.height(rowHeight);
		icon.width(rowHeight);

		if (rowData.activity == 'sleeping')
		{
			block.addClass(rowData.lastBuildStatus.toLowerCase());
			icon.attr('src', 'images/' + rowData.lastBuildStatus.toLowerCase() + '.png');
		}
		else
		{
			if (rowData.lastBuildStatus && rowData.lastBuildStatus.toLowerCase() != '')
			{
				block.addClass(rowData.lastBuildStatus.toLowerCase());
				icon.attr('src', 'images/building_' + rowData.lastBuildStatus.toLowerCase() + '.gif');
			}
			else
			{
				block.addClass('new');
				icon.attr('src', 'images/building.gif');
			}
			block.addClass('building');
		}

		// Add icon block
		var iconBlock = $('<div class="icon"></div>');
		iconBlock.height(rowHeight);
		iconBlock.width(rowHeight);
		iconBlock.append(icon);
		block.append(iconBlock);

		// User picture
		if (rowData.modification && rowData.modification.userPicture)
		{
			var userPictureBlock = $('<div class="userPicture" />');
			userPictureBlock.width(rowHeight - 6);
			userPictureBlock.height(rowHeight - 6);
			userPictureBlock.css('line-height', (rowHeight - 6) + 'px');

			var userPicture = $('<img />');
			userPicture.attr('src', 'user_pictures/' + rowData.modification.userPicture);
			userPictureBlock.append(userPicture);
		}
		else
			var userPictureBlock = null;

		// Failed tests
		var failedTestsBlock = $('<div class="failedTests" />');
		failedTestsBlock.css({'overflow': 'visible', 'white-space': 'nowrap', 'font-size': (rowHeight * .6 / 4) + 'px', 'line-height': (rowHeight / 4 ) + 'px'});

		var totalTestsBlock = $('<div class="totalTests" />');
		totalTestsBlock.css({'position': 'absolute', 'width': ($('#content').width() - 15) + 'px', 'overflow': 'hidden', 'white-space': 'nowrap', 'font-size': (rowHeight * .6 / 2) + 'px', 'line-height': rowHeight + 'px'});
		failedTestsBlock.append(totalTestsBlock);

		var strTotalTests = '';
		if (rowData.failedTests && rowData.failedTests.length > 0)
		{
			strTotalTests = (rowData.errors + rowData.failures) + '/' + rowData.tests;

			for(i = 0; i < rowData.failedTests.length; i++)
			{
				if (i < 3)
					failedTestsBlock.append(rowData.failedTests[i].name + '&nbsp;(' + (rowData.failedTests[i].failures + rowData.failedTests[i].errors) + '/' + rowData.failedTests[i].tests + ')<br />');
				else
				{
					failedTestsBlock.append('&hellip;and <b>' + (rowData.failedTests.length - i) + '</b> more');
					break;
				}
			}
		}
		else
			strTotalTests = rowData.tests;

		totalTestsBlock.html('<b>' + strTotalTests + '</b>');

		// Project name, build dates and revision
		var projectBlock = $('<div class="project"></div>');
		var projectNameBlock = $('<div class="projectName" />');
		var lastBuildBlock   = $('<span class="lastBuild" />');
		var buildTimeBlock   = $('<span class="buildTime" />');

		var revision = '';
		if (rowData.modification && rowData.modification.revision)
		{
			var revMatches = rowData.modification.revision.match(/([0-9]+):([0-9a-f]{12})([0-9a-f]{21})/);
			if (revMatches)
				revision = revMatches[2];
			else
				revision = rowData.modification.revision;

			revision = '&nbsp;rev.&nbsp;' + revision;
		}

		projectNameBlock.append(rowData.name);
		lastBuildBlock.append(rowData.strTimeAgo + '&nbsp;');
		buildTimeBlock.append('(' + rowData.strTotalTime + ')' + revision);

		projectBlock.append(projectNameBlock).append(lastBuildBlock).append('<br />').append(buildTimeBlock);
		projectBlock.height(rowHeight);

		var fontSize = rowHeight * .75;
		var maxWidth    = rowWidth / 2 - rowHeight;
		projectNameBlock.css({'clear' : 'both', 'overflow': 'visible', 'white-space': 'nowrap', 'font-size': Math.floor(fontSize * .5) + 'px', 'line-height': Math.floor(rowHeight * .5) + 'px'});
		lastBuildBlock.css({'white-space': 'nowrap', 'font-size': Math.floor(fontSize * .25) + 'px', 'line-height': Math.floor(rowHeight * .25) + 'px'});
		buildTimeBlock.css({'white-space': 'nowrap', 'font-size': Math.floor(fontSize * .25) + 'px', 'line-height': Math.floor(rowHeight * .25) + 'px'});

		// Append project block
		block.append(projectBlock);

		// Append user picture block, if set
		if (userPictureBlock != null)
			block.append(userPictureBlock);

		// Append failed tests block, if set
		if (failedTestsBlock != null)
			block.append(failedTestsBlock);

		// Block exists: only update modified elements
		if ($('#content #row_' + rowId).length > 0)
		{
			if ($('#content #row_' + rowId + ' div.icon img').attr('src') != icon.attr('src'))
				$('#content #row_' + rowId).replaceWith(block);
			else
			{
				$('#content #row_' + rowId + ' div.project').replaceWith(projectBlock);

				var currentUserPictureBlock = $('#content #row_' + rowId + ' div.userPicture');
				var currentFailedTestsBlock = $('#content #row_' + rowId + ' div.failedTests');

				currentUserPictureBlock.remove();
				currentFailedTestsBlock.remove();

				if (userPictureBlock != null)
					$('#content #row_' + rowId).append(userPictureBlock);

				if (failedTestsBlock != null)
					$('#content #row_' + rowId).append(failedTestsBlock);

				$('#content #row_' + rowId).height(rowHeight);
			}
		}
		else
			$('#content').append(block);

		// Resize project name block width if there is a user picture or a failed tests
		if (userPictureBlock != null || failedTestsBlock != null)
		{
			adjustFontSize(projectNameBlock, fontSize * .5,  maxWidth);
			adjustFontSize(lastBuildBlock,   fontSize * .25, maxWidth);
			adjustFontSize(buildTimeBlock,   fontSize * .25, maxWidth);
			projectBlock.width(maxWidth);
		}

		projectBlock.css('overflow', 'hidden');
	});
}

function adjustFontSize(block, fontSize, maxWidth)
{
	var actualWidth = block.width();

	// Adjust font size to fit the container
	if (actualWidth > maxWidth)
		block.css('font-size', Math.floor(.97 * fontSize * maxWidth / actualWidth) + 'px');
}