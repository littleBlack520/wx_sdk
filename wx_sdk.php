<?php
header('Content-type: application/json;charset=utf8');
class WxSdk{
    const APPID = "your APPID"; //设置你自己的APPID
    const APPSECRET = "your APPSECRET"; //设置你自己的APPSECRET
    //为了安全，用md5随机生成两个文件名。
    const JSAPI_TICKET_FILE =  "f867061f1d66b72aa3c7d7de33128b8c"; //保存jsapi_ticket的文件名
    const ACCESS_TOKEN_FILE =  "bee1ced774d417af1ca4596f6d4acad3"; //保存access_token的文件名
    public function __construct()
    {
        $this->getSignature();
    }

    /**
     * 获取signature签名
     */
    private  function  getSignature(){
        $cache = $this->hasCache(self::JSAPI_TICKET_FILE);
          if(!$cache ){
               $jsapi_ticket =  $this->getJsapiTicket();
               $jsapi_ticket || die('获取jsapi_ticket失败');
          }else{
              $jsapi_ticket = $cache;
          }
         $noncestr = $this->getRandomStr();
         $timestamp = time();
         //这里URL需要动态获取，否则会出错。
         $url = $_SERVER['HTTP_REFERER'];
         $data = "jsapi_ticket=${jsapi_ticket}&noncestr=${noncestr}&timestamp=${timestamp}&url=${url}";
         $signature = sha1($data);
         $json  = json_encode([
            'nonceStr'=>$noncestr,
            'signature'=>$signature,
            'timestamp'=> $timestamp,
            'appId'=>self::APPID,
        ]);
        if($_GET['callback']){
          echo $_GET['callback'].'('.$json.')';
        }else{
          echo $json;
        }


    }
    /**
     * 获取jsapi_ticket
     * @return bool  jsapi_ticket或者false
     */
    private  function getJsapiTicket(){
        $cache = $this->hasCache(self::ACCESS_TOKEN_FILE);
        if(!$cache ){
            $access_token =  $this->getAccessToken();
        }else{
            $access_token =   $cache;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket";
        $data =  [ 'access_token'=> $access_token, 'type' => 'jsapi' ];
        $result =   json_decode( $this->doCurl($url,$data)) ;
        if($result->ticket){
            $this->saveFile(self::JSAPI_TICKET_FILE,$result->ticket,$result->expires_in);
            return $result->ticket;
        }else{
            return false;
        }

    }

    /**
     * 获取access_token
     * @return bool  access_token或者false
     */
    private  function getAccessToken(){
         $url = "https://api.weixin.qq.com/cgi-bin/token";
         $data =  [ 'grant_type'=> "client_credential", 'appid' => self::APPID , 'secret'=>self::APPSECRET ];
         $result =   json_decode( $this->doCurl($url,$data)) ;
         if($result->access_token){
            $this->saveFile(self::ACCESS_TOKEN_FILE,$result->access_token,$result->expires_in);
             return $result->access_token;
         }else{
             return false;
         }
    }

    /**
     * 缓存到文件中
     * @param $filename 文件名
     * @param $value 签名值
     * @param $expires_in 时效
     */
    private  function saveFile($filename,$value,$expires_in){
        $handle=fopen($filename,"w");
        $time = time() +  $expires_in*1000;
        //保存为json格式的。
        $content = json_encode(['value'=>$value , 'time'=> $time]);
        //在linux中需要注意，文件写入的权限问题。
        fwrite($handle,$content);
        fclose($handle);
    }

    /**
     * 是否有缓存
     * @param $filename 文件路径
     * @return bool 缓存的值或者false
     */
    private  function  hasCache($filename){
     $str = "";
     if(file_exists($filename)){
          $handle = fopen("$filename", "r");
          if ($handle) {
              //读取文件
              while (!feof($handle)) {
                  $item = fgets($handle);
                  $str .= $item;
              }
              $result =  json_decode($str);
              //判断是否过期了。
              if(  time() >  $result->time  ){
                  return false;
              }else{
                  return $result->value;
              }
          }else{
            return false;
          }
      }else{
          return false;
      }
    }


    /**
     * 获随机字符串
     * @return string 随机字符串
     */
    private  function getRandomStr(){
          $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
          $length = 10;
          $result = '';
          for($i = 0;$i<$length;$i++ ){
               $result.=  $chars[ mt_rand(0,strlen( $chars)-1)];
          }
          return $result;
    }

    /**
     * 发送请求
     * @param $url 地址
     * @param $data 参数
     * @param int $timeout 超时
     * @return bool|mixed 结果资源
     */
    private function doCurl($url,$data,$timeout=1000){
         if($url == "" || $timeout <=0){
             return false;
         }else{
             $url =  $url."?".http_build_query($data);
             $ci = curl_init($url);
             curl_setopt($ci,CURLOPT_HEADER,false);
             curl_setopt($ci,CURLOPT_RETURNTRANSFER,true);
             curl_setopt($ci,CURLOPT_TIMEOUT,$timeout);
             //需关闭SSL验证
             curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
             $result =  curl_exec($ci);
             curl_close($ci);
             return $result;
         }
    }

}
//实例化。
new WxSdk();
echo phpinfo();
echo 'hello';
echo 'world'
