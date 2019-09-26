<?php

function connDb() 
{
	$conn = mysqli_connect('127.0.0.1', 'root', 'k343ks4s', 'study');
	if (!$conn) {
	    echo "Error: Unable to connect to MySQL." . PHP_EOL;
	    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
	    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
	    exit;
	}

	$conn->query("set names utf8");
	return $conn;
}

function recordTimeBlock($inputPath, $beginDate, $endDate)
{
	$conn = connDb();
	$errorCount = $successCount = 0;
	// 读取记录并插入数据库
	if (($handle = fopen($inputPath, "r")) != FALSE) {
		while (($data = fgetcsv($handle, 1000, ",")) != FALSE) {
			// 过滤不满足条件记录
			if (strtotime($data[0]) < strtotime($beginDate)) {
				continue;
			}
			if (strtotime($data[0]) >= strtotime($endDate)) {
				break;
			}
			
			// 解析数据
			$beginTime = $data[0];
			$endTime = date('Y-m-d H:i', (strtotime($data[0]) + $data[1] * 60));
			$durationTime = $data[1];
			$eventMainType = $data[2];
			$eventType = $data[3];
			$contentType = $data[4];
			$content = $data[5];
			$remark = $data[6];
			$tag = $data[7];
			$status = $data[8] == '已完成' ? 1 : 0;

			// 构建SQL插入数据
			$inserSql = sprintf("INSERT INTO `time_block` (begin_time, end_time, duration_time, event_main_type, event_type, content_type, content, remark, tag, status) VALUES('%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', %d)", $beginTime, $endTime, $durationTime, $eventMainType, $eventType, $contentType, $content, $remark, $tag, $status);
			//echo $status . "::" . $inserSql . "\n";
			$result = $conn->query($inserSql);
			if (!$result) {
				$errorCount++;
				echo "插入失败" . ",原因：" . mysqli_error($conn) . "\n";
			} else {
				$successCount++;
			}
		}

		echo "完成数据读取并入库，成功：" . $successCount . "，失败：" . $errorCount . "\n";

		fclose($handle);
	}

	$conn->close();
}

function outputToCsv($handle, $content)
{
	$data = [];
	foreach ($content as $value) {
		$data[] = iconv('UTF-8', 'GBK//TRANSLIT', $value);
	}
	fputcsv($handle, $data);
	//fputs($handle, iconv('UTF-8', 'GBK//TRANSLIT', implode(",", $content) . "\n"));
}

function exportToExcel($conn, $outputPath, $beginDate, $endDate)
{
	$count = 0;
	// 输出当月内容
	$exportSql = "select begin_time, duration_time, event_type, content, remark from time_block where  begin_time >= '{$beginDate}' and begin_time < '{$endDate}' ";
	$retval = $conn->query($exportSql);
	if (! $retval) {
		die('无法获取数据：' . mysqli_error($conn));
	}

	$outputFileHandle = fopen($outputPath, 'w');
	// fputcsv($outputFileHandle, ['开始时间', '持续时间', '事件类别', '事件内容', '备注']);
	$header = ['开始时间', '持续时间(h)', '事件类别', '事件内容', '备注'];
	outputToCsv($outputFileHandle, $header);
	
	while ($row = mysqli_fetch_array($retval, MYSQLI_ASSOC)) {
		$count++;
		$row['duration_time'] = sprintf("%.2f", $row['duration_time'] / 60);
		outputToCsv($outputFileHandle, $row);
	}
	echo "成功写入数据共" . $count . "条\n";
	fclose($outputFileHandle);
}

function statByType($conn, $beginDate, $endDate)
{
	$statByTypeSql = "select sum(`duration_time`) / 60 as `total_time`,`event_type` from time_block where begin_time >= '{$beginDate}' and begin_time < '{$endDate}' group by  `event_type` order by `total_time` desc";
	$statByTypeval = $conn->query($statByTypeSql);
	// echo $statByTypeSql . "\n";
	$typeArr = [];
	$sumTotalTime = 0;
	while ($row = mysqli_fetch_array($statByTypeval, MYSQLI_ASSOC)) {
		$typeItem = [];
		$typeItem['event_type'] = $row['event_type'];
		$typeItem['total_time'] = sprintf("%.2f", $row['total_time']);
		$sumTotalTime += $typeItem['total_time'];
		$typeArr[] = $typeItem;
	}

	foreach ($typeArr as &$type) {
		$type['ratio'] = sprintf("%.4f", $type['total_time'] / $sumTotalTime) * 100 . "%";
	}
	unset($type);
	echo "事件类别 ：耗时(共{$sumTotalTime}h) ：比例\n";
	foreach ($typeArr as $type) {
		echo $type['event_type'] . " : " . $type['total_time'] . " : " . $type['ratio'] . "\n";
	}
	echo "--------------------\n\n";

	return $typeArr;
}

function statByContent($conn, $beginDate, $endDate, $types)
{
	if (!is_array($types)) {
		$types = [$types];
	}
	$typesStr = implode("','", $types);
	$statByContentSql = "select sum(`duration_time`) / 60 as `total_time`,`content` from time_block where begin_time >= '{$beginDate}' and begin_time < '{$endDate}' and event_type in ('{$typesStr}') group by  `content` order by `total_time` desc";
	// echo $statByContentSql . "\n";
	$statByContentval = $conn->query($statByContentSql);
	if (!$statByContentval) {
		echo "查询失败" . ",原因：" . mysqli_error($conn) . "\n";
	}
	
	$contentArr = [];
	$sumTotalTime = 0;
	while ($row = mysqli_fetch_array($statByContentval, MYSQLI_ASSOC)) {
		$contentItem = [];
		$contentItem['content'] = $row['content'];
		$contentItem['total_time'] = sprintf("%.2f", $row['total_time']);
		$sumTotalTime += $contentItem['total_time'];
		$contentArr[] = $contentItem;
	}

	foreach ($contentArr as &$content) {
		$content['ratio'] = sprintf("%.4f", $content['total_time'] / $sumTotalTime) * 100 . "%";
	}
	unset($content);

	echo implode("&", $types) . "事件内容统计\n";
	echo "事件内容 ：耗时(共计{$sumTotalTime}h) ：比例\n";
	foreach ($contentArr as $content) {
		echo $content['content'] . " : " . $content['total_time'] . " : " . $content['ratio'] . "\n";
	}
	echo "--------------------\n\n";
	return $contentArr;
}

function exportTimeBlock($outputPath, $beginDate, $endDate)
{
	$conn = connDb();
	exportToExcel($conn, $outputPath, $beginDate, $endDate);
	$conn->close();
}

function statTimeBlock($outputPath, $beginDate, $endDate)
{
	$conn = connDb();

	$statFileHandle = fopen($outputPath, 'w');
	// 按事件类别统计
	$contentTypeArr = statByType($conn, $beginDate, $endDate);
	$sumTotalTime = 0;
	foreach ($contentTypeArr as $contentType) {
		$sumTotalTime += $contentType['total_time'];
	}
	$header = ["事件类别", "耗时(共{$sumTotalTime}h)", "比例"];
	outputToCsv($statFileHandle, $header);
	foreach ($contentTypeArr as $contentType) {
		outputToCsv($statFileHandle, $contentType);
	}
	outputToCsv($statFileHandle, ['', '', '']);


	// 按事件内容统计并输出
	$types = [['学习', '阅读'], '工作', '生活', '休闲娱乐'];
	foreach ($types as $type) {
		if (!is_array($type)) {
			$type = [$type];
		}

		$contentArr = statByContent($conn, $beginDate, $endDate, $type);
		$sumTotalTime = 0;
		foreach ($contentArr as $content) {
			$sumTotalTime += $content['total_time'];
		}

		$contentHeader = "事件内容(" . implode('&', $type) . ")";
		$header = [$contentHeader, "耗时(共{$sumTotalTime}h)", "比例"];
		outputToCsv($statFileHandle, $header);
		foreach ($contentArr as $content) {
			outputToCsv($statFileHandle, $content);
		}
		outputToCsv($statFileHandle, ['', '', '']);
	}
	fclose($statFileHandle);
	
	$conn->close();
}


$basePath = "/Users/yugang/Downloads";
$path = $basePath . "/export_timeblock.csv";
$beginDate = '2018-07-01';
$endDate = '2019-08-01';
$action = 'stat';
if ($argc >= 4) {
	$action = $argv[1];
	$beginDate = $argv[2];
	$endDate = $argv[3];
	isset($argv[4]) && $path = $argv[4];
}
$outputPath = $basePath . "/timeblock_list_" . $beginDate . "~" . $endDate . ".csv";
$statOutputPath = $basePath . "/timeblock_stat_" . $beginDate . "~" . $endDate . ".csv";
//var_dump($action, $beginDate, $endDate, $path, $outputPath);
if ($action == 'record') {
	recordTimeBlock($path, $beginDate, $endDate);
} elseif ($action == 'stat') {
	exportTimeBlock($outputPath, $beginDate, $endDate);
	statTimeBlock($statOutputPath, $beginDate, $endDate);
}



