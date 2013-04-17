<?php

class Crawler
{
    public function getList()
    {
	$list = array();
	$list['orgCode'] = array(
	    "01" => "股份有限公司",
	    "02" => "有限公司",
	    "03" => "無限公司",
	    "04" => "兩合公司",
	    "05" => "合夥",
	    "06" => "獨資",
	    "07" => "外國公司認許",
	    "08" => "外國公司報備",
	    "09" => "本國公司之分公司",
	    "10" => "外國公司之分公司",
	    "11" => "合作社",
	    "12" => "農會組織",
	    "13" => "公營",
	    "14" => "漁會",
	    "15" => "大陸公司許可登記",
	    "16" => "大陸公司許可報備",
	    "17" => "大陸公司之分公司",
	    "99" => "其他",
	);

	$list['statCode'] = array(
	    "00" => "生產中",
	    "01" => "停工",
	    "02" => "歇業",
	    "03" => "設立未登記",
	    "04" => "公告註銷",
	    "05" => "設立許可逾期失效",
	    "06" => "設立許可註銷",
	    "07" => "設立許可撤銷",
	    "08" => "設立許可廢止",
	    "09" => "歇業-遷廠",
	    "10" => "歇業-產業類別變更",
	    "11" => "歇業-關廠",
	    "12" => "校正後廢止",
	    "13" => "勒令停工-工業主管機關",
	    "14" => "勒令停工-勞工主管機關",
	    "16" => "勒令停工-消防主管機關",
	    "17" => "勒令停工-其他",
	);

	$list['cityCode1'] = array(
	    "630" => "臺北市",
	    "10017" => "基隆市",
	    "650" => "新北市",
	    "10002" => "宜蘭縣",
	    "10004" => "新竹縣",
	    "10018" => "新竹市",
	    "10003" => "桃園縣",
	    "10005" => "苗栗縣",
	    "660" => "臺中市",
	    "10007" => "彰化縣",
	    "10008" => "南投縣",
	    "10020" => "嘉義市",
	    "10010" => "嘉義縣",
	    "10009" => "雲林縣",
	    "670" => "臺南市",
	    "640" => "高雄市",
	    "10016" => "澎湖縣",
	    "10013" => "屏東縣",
	    "10014" => "臺東縣",
	    "10015" => "花蓮縣",
	    "09020" => "金門縣",
	    "09007" => "連江縣",
	);

	return $list;
    }

    protected $_curl = null;

    public function http($url, $post_params)
    {
	if (is_null($this->_curl)) {
	    $this->_curl = curl_init();
	}
	$curl = $this->_curl;
	$terms = array();
	foreach ($post_params as $k => $v) {
	    $terms[] = urlencode($k) . '=' . urlencode($v);
	}
	curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', $terms));
	curl_setopt($curl, CURLOPT_COOKIEFILE, 'cookie-file');
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_REFERER, 'http://gcis.nat.gov.tw/Fidbweb/factInfoListAction.do');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$content = curl_exec($curl);

	return str_replace('charset=MS950', 'charset=Big5-2003', $content);
    }

    public function getListFromContent($content)
    {
	$doc = new DOMDocument;
	@$doc->loadHTML($content);
	$table_doms = $doc->getElementsByTagName('table');
	$table_dom = $table_doms->item(0);
	$tr_doms = $table_dom->getElementsByTagName('tr');
	$c = 0;
	$rows = array();
	foreach ($tr_doms as $tr_dom) {
	    $td_doms = $tr_dom->getElementsByTagName('td');
	    if ($td_doms->length != 2) {
		continue;
	    }
	    $number_a_dom = $td_doms->item(0)->getElementsByTagName('a')->item(0);
	    $name_a_dom = $td_doms->item(1)->getElementsByTagName('a')->item(0);
	    if (is_null($number_a_dom) or is_null($name_a_dom)) {
		continue;
	    }
	    $link = $name_a_dom->getAttribute('href');
	    $number = trim($number_a_dom->nodeValue);
	    $name = trim($name_a_dom->nodeValue);

	    $rows[] = array($link, $number, $name);
	}
	return $rows;
    }

    public function getMaxPageFromContent($content)
    {
	$doc = new DOMDocument;
	@$doc->loadHTML($content);

	$select_doms = $doc->getElementsByTagName('select');
	$last_option_value = null;
	if (!$select_doms->item(0)) {
	    echo iconv('big5-2003', 'utf-8', $content) . "\n";
	    exit;
	}
	foreach ($select_doms->item(0)->getElementsByTagName('option') as $option_dom) {
	    $last_option_value = $option_dom->getAttribute('value');
	}
	return $last_option_value;
    }

    public function main()
    {
	$list = $this->getList();

	foreach ($list['orgCode'] as $orgCode_id => $orgCode_name) {
	    foreach ($list['statCode'] as $statCode_id => $statCode_name) {
		foreach ($list['cityCode1'] as $cityCode1_id => $cityCode1_name) {
		    $post_params = array(
			"method" => "query",
			"regiID" => "",
			"estbID" => "",
			"factName" => "",
			"addrCityCode1" => "JJ+%BD%D0%BF%EF%BE%DC",
			"addrCityCode2" => "JJ",
			"factAddr" => "",
			"orgCode" => $orgCode_id,
			"statCode" => $statCode_id,
			"cityCode1" => $cityCode1_id,
			"cityCode2" => "JJ",
			"profItem" => "JJ",
			"prodItem" => "",
			"prodItemCode" => "",
			"isFoodAdditionVal" => "",
			"profItemValue" => "undefined",
			"tmp_profitem" => "JJ",
		    );

		    $this->http('http://gcis.nat.gov.tw/Fidbweb/index.jsp', array());

		    $url = 'http://gcis.nat.gov.tw/Fidbweb/factInfoListAction.do';
		    $content = $this->http($url, $post_params);
		    sleep(1);
		    $rows = $this->getListFromContent($content);
		    $max_page = $this->getMaxPageFromContent($content);
		    foreach ($rows as $row) {
			echo $orgCode_name . "," . $statCode_name . "," . $cityCode1_name . "," . implode(',', $row). "\n";
		    }

		    for ($i = 1; $i <= $max_page; $i ++) {
			$url = 'http://gcis.nat.gov.tw/Fidbweb/factInfoListAction.do';
			$content = $this->http($url, array('method' => 'nextPage', 'goPage' => $i));
			$rows = $this->getListFromContent($content);
			$max_page = $this->getMaxPageFromContent($content);
			foreach ($rows as $row) {
			    echo $orgCode_name . "," . $statCode_name . "," . $cityCode1_name . "," . implode(',', $row). "\n";
			}

			sleep(1);
		    }
		}
	    }
	}
    }
}

$crawler = new Crawler;
$crawler->main();

