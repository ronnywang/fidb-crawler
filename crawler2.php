<?php

class Crawler
{
    public function http($url)
    {
	$url = 'http://gcis.nat.gov.tw' . $url;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_URL, $url);
	$content = curl_exec($curl);

	return str_replace('charset=MS950', 'charset=Big5-2003', $content);
    }

    public function parse($content)
    {
	$doc = new DOMDocument;
	@$doc->loadHTML($content);
	$table_dom = $doc->getElementById('AutoNumber3');

	$children_table_doms = $table_dom->getElementsByTagName('table');
	if (3 !== $children_table_doms->length) {
	    throw new Exception('不是三個啊～～～');
	}

	// 處理第一個 table
	$table_dom1 = $children_table_doms->item(0);
	$info = new StdClass;
	foreach ($table_dom1->getElementsByTagName('tr') as $tr_dom) {
	    $td_doms = $tr_dom->getElementsByTagName('td');
	    $k = $v = null;
	    for ($i = 0; $i < $td_doms->length; $i ++) {
		if ($i % 2 == 0) {
		    $k = trim($td_doms->item($i)->nodeValue);
		} else {
		    $v = trim($td_doms->item($i)->nodeValue);
		    $info->{$k} = $v;
		}
	    }
	}

	// 處理備註的 table
	$table_dom2 = $children_table_doms->item(1);
	$info->{'備註'} = $table_dom2->nodeValue;

	// 處理第三個 table
	$table_dom3 = $children_table_doms->item(2);
	$tr_doms = $table_dom3->getElementsByTagName('tr');
	if (5 != $tr_doms->length) {
	    throw new Exception("我預期第三個 table 應該要有五欄才對");
	}
	$info->{'修訂資訊'} = $tr_doms->item(0)->nodeValue;
	if ('產業類別' != trim($tr_doms->item(1)->nodeValue)) {
	    throw new Exception("我預期第三個 table 的第二欄應該是產業類別");
	}
	if ('主要產品' != trim($tr_doms->item(3)->nodeValue)) {
	    throw new Exception("我預期第三個 table 的第四欄應該是產業類別");
	}

	// 處理產業類別
	$font_doms = $tr_doms->item(2)->getElementsByTagName('font');
	if ($font_doms->length !== 1) {
	    throw new Exception("我預期第三個 table 的第三欄應該只有一個 table");
	}
	$rows = array();
	foreach ($font_doms->item(0)->childNodes as $childNode) {
	    if ($childNode->nodeType != XML_TEXT_NODE) {
		continue;
	    }
	    $rows[] = preg_split('/\s+/', trim($childNode->nodeValue), 2);
	}
	$info->{'產業類別'} = $rows;

	// 處理主要產品
	$font_doms = $tr_doms->item(4)->getElementsByTagName('font');
	if ($font_doms->length !== 1) {
	    throw new Exception("我預期第三個 table 的第三欄應該只有一個 table");
	}
	$rows = array();
	foreach ($font_doms->item(0)->childNodes as $childNode) {
	    if ($childNode->nodeType != XML_TEXT_NODE) {
		continue;
	    }
	    $rows[] = preg_split('/\s+/', trim($childNode->nodeValue), 2);
	}
	$info->{'主要產品'} = $rows;

	return $info;
    }

    public function main()
    {
	$fp = fopen('factor_list', 'r');
	while (false !== ($line = fgets($fp))) {
	    list($value1, $value2, $value3, $url, $no, $name) = explode(',', $line);

	    if (file_exists(__DIR__ . '/info/' . $no)) {
		continue;
	    }

	    $content = $this->http($url);
	    $info = $this->parse($content);
	    $info->{'工廠類型'} = $value1; // Ex: 股份有限公司
	    $info->{'生產狀況'} = $value2; // Ex: 生產中
	    $info->{'縣市'} = $value3; // Ex: 台北市
	    $info->{'網址'} = $url;
	    $info->{'工業編號'} = $no;
	    $info->{'工廠名稱'} = $name;

	    file_put_contents(__DIR__ . '/info/' . $no, json_encode($info));
	}
    }
}

$c = new Crawler;
$c->main();
