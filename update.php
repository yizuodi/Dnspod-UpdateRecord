<?php
// Dnspod token，获取地址 https://console.dnspod.cn/account/token/token
$dnspodtoken = '123456,zglgmyg123ttzthsc456nwbcgsg789';

$token = $_GET['token'];
if($token != 'yourtoken')  // 请自行随心设置，请求时需要附带token
{
    exit("token缺失/错误");
}

$domain = $_GET['domain']; // 根域名 eg. example.com
$record = $_GET['record']; // 子域(记录) eg. www 
$value = $_GET['value']; // 记录值 eg. 1.1.1.1
$record_type = $_GET['type']; // 记录类型 eg. A
if (empty($record_type)) {
    $record_type = 'A';
}
if (empty($domain)||empty($record)||empty($value)) {
    exit("domain/record/value参数缺失\n");
}

// 获取根域所有解析列表
$api = 'https://dnsapi.cn/Record.List';
$post = [
    'login_token' => $dnspodtoken,
    'format'      => 'json',
    'lang'        => 'cn',
    'domain'      => $domain,
];
$record_id = null;
$record_value = null;
$res = getCurl($api, ['post' => $post]);
$data = json_decode($res, true);

// 获取子域对应记录ID
foreach ($data['records'] as $itm) {
    if ($record == $itm['name']) {
        $record_id = $itm['id'];
        $record_value = $itm['value'];
    }
}

if (empty($record_id)) {
    exit("记录 [$record] 未找到，请检查！\n");
}


// 修改域名记录
if($record_value != $value)
{
    echo "old value：$record_value \n";
    echo "new value：$value \n";
    $api  = 'https://dnsapi.cn/Record.Modify';
    $post = [
        'login_token' => $dnspodtoken,
        'format'      => 'json',
        'lang'        => 'cn',
        'domain'      => $domain,
        'record_id'   => $record_id,
        'sub_domain'  => $record,
        'record_type' => $record_type,
        'record_line' => '默认',
        'value'       => $value,
        'mx'          => 20,
    ];
    $res  = getCurl($api, ['post' => $post]);
    $data = json_decode($res, true);
    if ($data['status']['code']) {
        echo "{$data['status']['message']} \n";
    }
}
else {
    echo "value：$record_value \n";
    echo '未传递修改请求';
}


function getCurl($url, $opt = [])
{
    $cookie = '';
    if (is_array($opt['cookie'])) {
        foreach ($opt['cookie'] as $k => $v) {
            $cookie .= $k . '=' . $v . '; ';
        }
    }

    $cookie = (mb_substr($cookie, 0, mb_strlen($cookie) - 2));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Expect:"]);
    curl_setopt($ch, CURLOPT_NOBODY, $opt['nobody']);
    curl_setopt($ch, CURLOPT_HEADER, $opt['header'] ?? false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $opt['headers'] ?? []);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, $opt['rtime'] ?? 10000);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $opt['ctime'] ?? 10000);
    curl_setopt($ch, CURLOPT_USERAGENT,
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.102 Safari/537.36');
    if (isset($opt['post'])) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($opt['post']) ? http_build_query($opt['post']) : $opt['post']);
    }
    if (isset($opt['proxy']) && is_array($opt['proxy'])) {
        curl_setopt($ch, CURLOPT_PROXY, $opt['proxy']['ip']);
        curl_setopt($ch, CURLOPT_PROXYPORT, $opt['proxy']['port']);
    }
    $res   = curl_exec($ch);
    $error = curl_error($ch);
    $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($opt['detail']) {
        return ['code' => $code, 'error' => $error, 'resp' => $res,];
    }

    return $res;
}
