<?php
/**
 * getFavicon
 * @author    一为
 * @date      2019-11-27
 * @link      https://www.iowen.cn
 * @version   1.1.0
 */
header("Content-type: text/html; charset=utf-8");

//如果需要设置允许所有域名发起的跨域请求，可以使用通配符 *
header("Access-Control-Allow-Origin: *");

header('Content-Type:application/json; charset=utf-8');

if( !isset($_GET['url'])){
    return http_response_code(404);
}

require "./Favicon.php";

$favicon = new \Jerrybendy\Favicon\Favicon;


/* ------ 参数设置 ------ */

$defaultIco='favicon.png';   //默认图标路径
$expire = 1;           //缓存有效期30天, 单位为:秒，为0时不缓存

/* ------ 参数设置 ------ */
/**
 * 设置默认图标
 */
$favicon->setDefaultIcon($defaultIco);

/**
 * 检测URL参数
 */

$url = empty($_GET["url"]) ? '' : $_GET["url"];
$type = empty($_GET["type"]) ? '' : $_GET["type"];

/*
 * 格式化 URL, 并尝试读取缓存
 */
$formatUrl = $favicon->formatUrl($url);

if($expire == 0){
    $favicon->getFavicon($formatUrl, true);
    exit;
}else{
	
    $defaultMD5 = md5(file_get_contents($defaultIco));
    $data = Cache::get($formatUrl,$defaultMD5,$expire);
	
    if ($data !== NULL) {
        // foreach ($favicon->getHeader() as $header) {
        //     @header($header);
        // }
        // echo $data;
		// echo $content;
	if($type==1){
		 //fopen以二进制方式打开
		 
		$context = stream_context_create(
			 [
				 'ssl' => [
					 'verify_peer' => false,
				 ]
			 ]);
		$handle=fopen($url,"rb", null, $context);
		//变量初始化
		//循环读取数据
		$lines_string="";
		do{
		    $data=fread($handle,1024);
		    if(strlen($data)==0) {
		        break;
		    }
		$lines_string.=$data;
		}while(true);
		//关闭fopen句柄，释放资源
		fclose($handle);
		
		$htlmText = mb_convert_encoding($lines_string,"utf-8", "auto");
		//正则提取，匹配次数
		$match_nums = preg_match_all('/<title>([\S\s]*?)<\/title>/',$htlmText, $matchs);
		//匹配项是一个二维数组
		//返回完整匹配次数（可能是0），或者如果发生错误返回FALSE。
		if($match_nums == 0 || $match_nums == FALSE ){
			//没有匹配就原样返回
			return array();
		}
		//第一个是完整匹配，第二个匹配就是去掉title标签的纯文本
		$rows['title'] =  $matchs[1][0];
	}
		$rows['list'] = 'data:' . 'image/png' . ';base64,' . chunk_split(base64_encode($data));
		// echo $content;
		$rows['code'] = 200;
		exit(json_encode($rows));
		exit();
    }

    /**
     * 缓存中没有指定的内容时, 重新获取内容并缓存起来
     */
    $content = $favicon->getFavicon($formatUrl, TRUE);

    if( md5($content) == $defaultMD5 ){
        $expire = 43200; //如果返回默认图标，设置过期时间为12小时。Cache::get 方法中需同时修改
    }

    Cache::set($formatUrl, $content, $expire);

    // foreach ($favicon->getHeader() as $header) {
    //     @header($header);
    // }
    // echo $content;
	// exit();
	
	if($type==1){
		 //fopen以二进制方式打开
		$context = stream_context_create(
			 [
				 'ssl' => [
					 'verify_peer' => false,
				 ]
			 ]);
		$handle=fopen($url,"rb", null, $context);
		//变量初始化
		//循环读取数据
		$lines_string="";
		do{
		    $data=fread($handle,1024);
		    if(strlen($data)==0) {
		        break;
		    }
		$lines_string.=$data;
		}while(true);
		//关闭fopen句柄，释放资源
		fclose($handle);
		$htlmText = mb_convert_encoding($lines_string,"utf-8", "auto");
		//正则提取，匹配次数
		$match_nums = preg_match_all('/<title>([\S\s]*?)<\/title>/',$htlmText, $matchs);
		//匹配项是一个二维数组
		//返回完整匹配次数（可能是0），或者如果发生错误返回FALSE。
		if($match_nums == 0 || $match_nums == FALSE ){
			//没有匹配就原样返回
			return array();
		}
		//第一个是完整匹配，第二个匹配就是去掉title标签的纯文本
		$rows['title'] =  $matchs[1][0];
	}
	$rows['list'] = 'data:' . 'image/png' . ';base64,' . chunk_split(base64_encode($content));
	$rows['code'] = 200;
	exit(json_encode($rows));
	exit();
}


/**
 * 缓存类
 */
class Cache
{
    /**
     * 获取缓存的值, 不存在时返回 null
     *
     * @param $key
     * @param $default  默认图片
     * @param $expire   过期时间
     * @return string
     */
    public static function get($key, $default, $expire)
    {
        $dir = 'cache'; //图标缓存目录
       
        //$f = md5( strtolower( $key ) );
        $f = parse_url($key)['host'];

        $a = $dir . '/' . $f . '.txt';

        if(is_file($a)){
            $data = file_get_contents($a);
            if( md5($data) == $default ){
                $expire = 43200; //如果返回默认图标，过期时间为12小时。
            }
            if( (time() - filemtime($a)) > $expire ){
                return null;
            }
            else{
                return $data;
            }
		}
        else{
            return null;
        }
    }

    /**
     * 设置缓存
     *
     * @param $key
     * @param $value
     * @param $expire   过期时间
     */
    public static function set($key, $value, $expire)
    {
        $dir = 'cache'; //图标缓存目录
        
        //$f = md5( strtolower( $key ) );
        $f = parse_url($key)['host'];

        $a = $dir . '/' . $f . '.txt';
        
        //如果缓存目录不存在则创建
        if (!is_dir($dir)) mkdir($dir,0777,true) or die('创建缓存目录失败！');

        if ( !is_file($a) || (time() - filemtime($a)) > $expire ) {
            $imgdata = fopen($a, "w") or die("Unable to open file!");  //w  重写  a追加
            fwrite($imgdata, $value);
            fclose($imgdata); 
        }
    }
}
