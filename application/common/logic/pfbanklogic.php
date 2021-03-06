<?php
namespace app\common\logic; 
/*********************************************************
*文件名：  浦发银行调用.php
*作者：狐狸<foxis@qq.com>
*创建时间：  2010年6月12日
*修改时间：
*功能描述： 话题模块相关的数据库操作
*使用方法：

******************************************************/
use tencentai\sdk\HttpUtil; 
use think\Cache;
class pfbanklogic extends Logic
{
 
    
    var $url='http://127.0.0.1:8080/';

    var $masterID='2017269860';
    var $_cache;
 

 
    function __construct($base = null)
    {
        // $this->client_id=fnapiConf_pub::client_id;
        // $this->client_secret=fnapiConf_pub::clientSecret;
        // $this->url=fnapiConf_pub::JS_API_CALL_URL;
 
    }
    //AQ49 实时单笔代收付交易
    //21.10 AQ52 批量代收付交易
	function payAQ52( $datas ){
 
		 if(is_array($datas)){
		 	$bankpay=config('bankpay');  

		 	$xmlbody="<transMasterID>".$this->masterID."</transMasterID><projectNumber>".$bankpay['projectNumber']."</projectNumber><projectName>".$bankpay['projectName']."</projectName><costItemCode>".$bankpay['picostItemCode']."</costItemCode><transType>2</transType><elecChequeNo>".$datas['elecChequeNo']."</elecChequeNo><batchNo>".$datas['elecChequeNo']."</batchNo>".'<totalNumber>'.count($datas['list']).'</totalNumber>'; 
		 	$amount=0;$xmllist='';
		 	foreach ($datas['list'] as $key => $value) {
		 		$xmllist.='<list><detailedContent>'.$value['detailedContent'].'</detailedContent></list>';

                $amount+=$value['amount'];
		 	}
		 	$xmlbody.='<totalAmount>'.$amount.'</totalAmount>'; 
		 }



		$preKey8801 = "<body>{$xmlbody}<lists name='LoopResult'>{$xmllist}</lists>" .
                "</body>"; 
		
		 /////请求获得签名

         /// 
          
        //$preKey8801=   iconv("UTF-8", "GBK", $preKey8801)   ;  
 
		@file_put_contents(APP_PATH.'../banklog/payAQ52'.date('Y-m-d ').'.log', print_r($preKey8801,true),FILE_APPEND);        
		
		//$preResult=dfopen("http://127.0.0.1:8080/sign?body=".$preKey8801 ); 
		$preResult=HttpUtil::doHttpPost( $this->url."sign" ,['body'=>$preKey8801] );///
		if($preResult){//print_r($preResult);
			$result=json_decode($preResult,true);
			if($result['sign']&&$result['code']==1){//成功
				//println("8801密文：" + preResult );
				 ////拿到sing内容，这样写只是为了快
				$sign =   $result['sign'];
				//组装最终的请求报文
				$str =  rand();///随机流水号
				$firstPart = "<?xml version='1.0' encoding='GB2312'?><packet><head><transCode>AQ52</transCode>" .
		        "<signFlag>1</signFlag><packetID>" . $str . "</packetID><masterID>".$this->masterID."</masterID>";

				$timeStamp = "<timeStamp>" .date('Y-m-d H:i:s') . "</timeStamp></head><body>";

				$sign = "<signature>" . $sign ."</signature></body></packet>";

				$postBody = $firstPart . $timeStamp .$sign;
				$postBody =(strlen(  $postBody) +6  ). $postBody;  //计算出长度 +6 代表本身占用的长度
			 	$postBody = "00" .$postBody; //补齐6位
				//start
 
				$preResult =  HttpUtil::doHttpPost($this->url."request", ['body'=>$postBody] ); 
				if($preResult){ 
					@file_put_contents(APP_PATH.'../banklog/payAQ52'.date('Y-m-d ').'.log', print_r($preResult,true),FILE_APPEND);   
					$result=json_decode($preResult,true); 
					if($result['code']==1){//成功
						$xml=xml2array($result['result']); 
						$acceptNo=$xml['body']['sic']['body']['handleSeqNo'];print_r($xml); 
						if($acceptNo){
							return $xml['body']['sic']['body']; 
						}else{
							return false;
						}

					}else{
						return  false;
					}
					
				}
			}else{
				return  false;
			}
		}else{
			return  false;
		}

		exit; 

	   
	} 

	//21.10 AQ53 批量代收付交易
	function payAQ53( $datas ){
  
		if(is_array($datas)){
		 	$bankpay=config('bankpay');  

		 	$xmlbody="<transMasterID>".$this->masterID."</transMasterID><projectNumber>".$bankpay['projectNumber']."</projectNumber><beginDate>20191011</beginDate><endDate>".date('Ymd')."</endDate><queryNumber>30</queryNumber><beginNumber>1</beginNumber>"; 
		 	 
		}
		 $preKey8801 = "<body>{$xmlbody}</body>"; 

 
		
		 /////请求获得签名

        @file_put_contents(APP_PATH.'../banklog/payAQ53'.date('Y-m-d ').'.log', print_r($preKey8801,true));        
		 /// 
          
        /// 
        //$preKey8801=   iconv("UTF-8", "GBK", $preKey8801)   ;  
 
		 
		//$preResult=dfopen("http://127.0.0.1:8080/sign?body=".$preKey8801 ); 
		$preResult=HttpUtil::doHttpPost( $this->url."sign" ,['body'=>$preKey8801] );///
		if($preResult){ 
			
			$result=json_decode($preResult,true);print_r($result);
			if($result['sign']&&$result['code']==1){//成功
				//println("8801密文：" + preResult );
				 ////拿到sing内容，这样写只是为了快
				$sign =   $result['sign'];
				//组装最终的请求报文
				$str =  rand();///随机流水号
				$firstPart = "<?xml version='1.0' encoding='GB2312'?><packet><head><transCode>AQ53</transCode>" .
		        "<signFlag>1</signFlag><packetID>" . $str . "</packetID><masterID>".$this->masterID."</masterID>";

				$timeStamp = "<timeStamp>" .date('Y-m-d H:i:s') . "</timeStamp></head><body>";

				$sign = "<signature>" . $sign ."</signature></body></packet>";

				$postBody = $firstPart . $timeStamp .$sign;
				$postBody =(strlen(  $postBody) +6  ). $postBody;  //计算出长度 +6 代表本身占用的长度
			 	$postBody = "00" .$postBody; //补齐6位
				//start
 
				$preResult =  HttpUtil::doHttpPost($this->url."request", ['body'=>$postBody] );   


				if($preResult){ 
					$result=json_decode($preResult,true);print_r($result);
					@file_put_contents(APP_PATH.'../banklog/payAQ53'.date('Y-m-d ').'.log', print_r($result,true),FILE_APPEND);        
		
					if($result['code']==1){//成功
						$xml=xml2array($result['result']);print_r($xml);
					}else{
						return  false;
					}
					
				}
			}else{
				return  false;
			}
		}else{
			return  false;
		}

		exit; 

	   
	} 

	//21.10 AQ53 批量代收付交易
	function payAQ54( $datas ){
  
		if(is_array($datas)){
		 	$bankpay=config('bankpay');  

		 	$xmlbody="<transMasterID>".$this->masterID."</transMasterID><projectNumber>".$bankpay['projectNumber']."</projectNumber><costItemCode>".$bankpay['picostItemCode']."</costItemCode><transDate>".date('Ymd')."</transDate><handleSeqNo>".$datas['handleSeqNo']."</handleSeqNo>"; 
		 	 
		}
		 $preKey8801 = "<body>{$xmlbody}</body>"; 

 
		
		 /////请求获得签名

         /// 
          
        /// 
        //$preKey8801=   iconv("UTF-8", "GBK", $preKey8801)   ;  
 
		@file_put_contents(APP_PATH.'../banklog/payAQ54'.date('Y-m-d ').'.log', print_r($preKey8801,true));        
		
		//$preResult=dfopen("http://127.0.0.1:8080/sign?body=".$preKey8801 ); 
		$preResult=HttpUtil::doHttpPost( $this->url."sign" ,['body'=>$preKey8801] );///
		if($preResult){ 
			
			$result=json_decode($preResult,true);//print_r($result);
			if($result['sign']&&$result['code']==1){//成功
				//println("8801密文：" + preResult );
				 ////拿到sing内容，这样写只是为了快
				$sign =   $result['sign'];
				//组装最终的请求报文
				$str =  rand();///随机流水号
				$firstPart = "<?xml version='1.0' encoding='GB2312'?><packet><head><transCode>AQ54</transCode>" .
		        "<signFlag>1</signFlag><packetID>" . $str . "</packetID><masterID>".$this->masterID."</masterID>";

				$timeStamp = "<timeStamp>" .date('Y-m-d H:i:s') . "</timeStamp></head><body>";

				$sign = "<signature>" . $sign ."</signature></body></packet>";

				$postBody = $firstPart . $timeStamp .$sign;
				$postBody =(strlen(  $postBody) +6  ). $postBody;  //计算出长度 +6 代表本身占用的长度
			 	$postBody = "00" .$postBody; //补齐6位
				//start
 
				$preResult =  HttpUtil::doHttpPost($this->url."request", ['body'=>$postBody] );   


				if($preResult){ 
					$result=json_decode($preResult,true);////print_r($result);
					@file_put_contents(APP_PATH.'../banklog/payAQ54'.date('Y-m-d ').'.log', print_r($result,true),FILE_APPEND);        
		
					if($result['code']==1){//成功
						$xml=xml2array($result['result']);
						$count=$xml['body']['sic']['body']['totalNumber'];
						$datasr=['count'=>$count ];
						if($count){
							$lists=$xml['body']['sic']['body']['lists']['list'];
							if(is_array($lists)){
								foreach ($lists as $key => $value) {
									if($count==1){$tvalue['detailedContent']=$value;}else{$tvalue=$value;}
									if(isset($tvalue['detailedContent'])){
										$t=explode('|', $tvalue['detailedContent']);
										//明细序号|是否浦发账户|收付款人对公对私标志|3银行卡卡类型|4账号|5账户名|证件类型|7证件号码|8对手行行号|对手行行名|支付行号|币种|12金额|手机号|企业流水号|备用信息|企业分支机构|摘要|备注|备用1|备用2|备用3|状态|错误信息
										$t['handleSeqNo']=$datas['handleSeqNo'];
										$datalist[]=$t;
									}
									
								}
							}//print_r($datalist);
							$datasr['code']= 1;$datasr['datalist']= $datalist;

						}else{
							$datasr['code']= 0;
						}
						return $datalist;
					}else{
						return  false;
					}
					
				}
			}else{
				return  false;
			}
		}else{
			return  false;
		}

		exit; 

	   
	} 

    
	//获取城市政策  写缓存
	function pay8801( $value ){

		//  $body['authMasterID']='';
		//  $body['elecChequeNo']='900013';///电子凭证号
		//  $body['acctNo']='952A9997220008092';//付款账号
		//  $body['acctName']='10';//付款人账户名称
		//  $body['bespeakDate']=date('Ymd');
		//  $body['payeeAcctNo']='1';///收款人账号
		//  $body['payeeName']='1';//收款人名称
		//  /*0-对公账号
		// 1-卡
		// 2-活期一本通
		// 3-定期一本通*/
		//  $body['payeeType']='1';
		//  $body['payeeBankName']='1';//收款行名称
		//  $body['payeeAddress']='1';//收款行地址
		//  $body['amount']='1';///支付金额
		//  /*0：表示本行
		// 1：表示他行*/
		//  $body['sysFlag']='1';
		//  /*0：同城
		// 1：异地*/
		//  $body['remitLocation']='1';
		//  $body['note']='1';//附言
		//  $body['payeeBankSelectFlag']='';
		//  $body['payeeBankNo']='';
		//  $data['body']=$body;
		//  $preKey8801=array2xml($data);


		$xmllist='<elecChequeNo>'.$value['elecChequeNo'].'</elecChequeNo><acctNo>'.$value['acctNo']."</acctNo>" .
                '<acctName>'.$value['acctName'].'</acctName><bespeakDate>'.date('Ymd').'</bespeakDate><payeeAcctNo>'.$value['payeeAcctNo'].'</payeeAcctNo><payeeName>'.$value['payeeName'].'</payeeName>' .
                '<payeeType>'.$value['payeeType'].'</payeeType><payeeBankName>'.$value['payeeBankName'].'</payeeBankName>' .
                '<payeeAddress>'.$value['payeeAddress'].'</payeeAddress><amount>'.$value['amount'].'</amount><sysFlag>'.$value['sysFlag'].'</sysFlag><remitLocation>0</remitLocation>'.
                '<note>'.$value['note'].'</note>';


		$preKey8801 = "<body>" .
                "<authMasterID></authMasterID>{$xmllist}" .
                "</body>"; 
		@file_put_contents(APP_PATH.'../banklog/8801'.date('Y-m-d ').'.log', print_r($preKey8801,true),FILE_APPEND);
		 /////请求获得签名
		 /// 
        $preKey8801=   iconv("UTF-8", "GBK", $preKey8801)   ;  
 
		 
		//$preResult=dfopen("http://127.0.0.1:8080/sign?body=".$preKey8801 ); 
		$preResult=HttpUtil::doHttpPost( $this->url."sign" ,['body'=>$preKey8801] );///
		if($preResult){ ///print_r($preResult);
			$result=json_decode($preResult,true);
			if($result['sign']&&$result['code']==1){//成功
				//println("8801密文：" + preResult );
				 ////拿到sing内容，这样写只是为了快
				$sign =   $result['sign'];
				//组装最终的请求报文
				$str =  rand();///随机流水号
				$firstPart = "<?xml version='1.0' encoding='GB2312'?><packet><head><transCode>8801</transCode>" .
		        "<signFlag>1</signFlag><packetID>" . $str . "</packetID><masterID>".$this->masterID."</masterID>";

				$timeStamp = "<timeStamp>" .date('Y-m-d H:i:s') . "</timeStamp></head><body>";

				$sign = "<signature>" . $sign ."</signature></body></packet>";

				$postBody = $firstPart . $timeStamp .$sign;
				$postBody =(strlen(  $postBody) +6  ). $postBody;  //计算出长度 +6 代表本身占用的长度
			 	$postBody = "00" .$postBody; //补齐6位
				//start

				$preResult =  HttpUtil::doHttpPost($this->url."request", ['body'=>$postBody] ); 
				if($preResult){@file_put_contents(APP_PATH.'../banklog/8801'.date('Y-m-d ').'.log', print_r($preResult,true),FILE_APPEND);
					$result=json_decode($preResult,true);
					if($result['code']==1){//成功
						$xml=xml2array($result['result']); 
						$transStatus=$xml['body']['sic']['body']['transStatus'];print_r($xml); 
						if($transStatus<=4){
							return $xml['body']['sic']['body']; 
						}else{
							return false;
						}

					}else{
						return  false;
					}
					//print_r($result);
				}


			}else{
				return  false;
			}
		}else{
			return  false;
		}

		exit;

		 


		

		
		/////System.out.println("first step" + preResult);

		//进行验签
		$signResult = split("</signature>",split("<signature>",$preResult)[1])[0];
		///System.out.println("-----" + signResult);
		$signResult =  post("http://127.0.0.1:4437/", $signResult, 3);
 
		 

	   
	}   

	//批量提现
	function pay8800( $datas ){
 
		 if(is_array($datas)){
		 	$xmlbody='<totalNumber>'.count($datas).'</totalNumber>'; 
		 	$amount=0;
		 	foreach ($datas as $key => $value) {
		 		$xmllist.='<list><elecChequeNo>'.$value['elecChequeNo'].'</elecChequeNo><acctNo>'.$value['acctNo']."</acctNo>" .
                '<acctName>'.$value['acctName'].'</acctName><bespeakDate>'.date('Ymd').'</bespeakDate><payeeAcctNo>'.$value['payeeAcctNo'].'</payeeAcctNo><payeeName>'.$value['payeeName'].'</payeeName>' .
                '<payeeType>'.$value['payeeType'].'</payeeType><payeeBankName>'.$value['payeeBankName'].'</payeeBankName>' .
                '<payeeAddress>'.$value['payeeAddress'].'</payeeAddress><amount>'.$value['amount'].'</amount><sysFlag>'.$value['sysFlag'].'</sysFlag><remitLocation>0</remitLocation>'.
                '<note>'.$value['note'].'</note></list>';

                $amount+=$value['amount'];
		 	}
		 	$xmlbody.='<totalAmount>'.$amount.'</totalAmount>'; 
		 }



		$preKey8801 = "<body><authMasterID></authMasterID>{$xmlbody}<lists name='PayList'>{$xmllist}</lists>" .
                "</body>"; 
		
		 /////请求获得签名

        @file_put_contents(APP_PATH.'../banklog/pay8800'.date('Y-m-d ').'.log', print_r($preKey8801,true),FILE_APPEND);        
		 /// 
          
        $preKey8801=   iconv("UTF-8", "GBK", $preKey8801)   ;  
 
		 
		//$preResult=dfopen("http://127.0.0.1:8080/sign?body=".$preKey8801 ); 
		$preResult=HttpUtil::doHttpPost( $this->url."sign" ,['body'=>$preKey8801] );///
		if($preResult){//print_r($preResult);
			$result=json_decode($preResult,true);
			if($result['sign']&&$result['code']==1){//成功
				//println("8801密文：" + preResult );
				 ////拿到sing内容，这样写只是为了快
				$sign =   $result['sign'];
				//组装最终的请求报文
				$str =  rand();///随机流水号
				$firstPart = "<?xml version='1.0' encoding='GB2312'?><packet><head><transCode>8800</transCode>" .
		        "<signFlag>1</signFlag><packetID>" . $str . "</packetID><masterID>".$this->masterID."</masterID>";

				$timeStamp = "<timeStamp>" .date('Y-m-d H:i:s') . "</timeStamp></head><body>";

				$sign = "<signature>" . $sign ."</signature></body></packet>";

				$postBody = $firstPart . $timeStamp .$sign;
				$postBody =(strlen(  $postBody) +6  ). $postBody;  //计算出长度 +6 代表本身占用的长度
			 	$postBody = "00" .$postBody; //补齐6位
				//start
 
				$preResult =  HttpUtil::doHttpPost($this->url."request", ['body'=>$postBody] ); 
				if($preResult){ 
					@file_put_contents(APP_PATH.'../banklog/pay8800'.date('Y-m-d ').'.log', print_r($preResult,true),FILE_APPEND);   
					$result=json_decode($preResult,true); 
					if($result['code']==1){//成功
						$xml=xml2array($result['result']); 
						$acceptNo=$xml['body']['sic']['body']['acceptNo'];print_r($xml); 
						if($acceptNo){
							return $xml['body']['sic']['body']; 
						}else{
							return false;
						}

					}else{
						return  false;
					}
					
				}
			}else{
				return  false;
			}
		}else{
			return  false;
		}

		exit; 

	   
	}   


	//5.5 8804 支付查询 
	function pay8804( $datas ){

		 



		$preKey8801 = "<body>" .
                "<elecChequeNo></elecChequeNo><acctNo>92030078801200000311</acctNo>" .
                "<beginDate>20190911</beginDate><endDate>".date('Ymd')."</endDate><queryNumber>30</queryNumber><beginNumber>1</beginNumber>" .
                "<singleOrBatchFlag>1</singleOrBatchFlag>" .
                "</body>"; 
		
		 /////请求获得签名
		 /// 
        $preKey8801=   iconv("UTF-8", "GBK", $preKey8801)   ;  
 
		 
		//$preResult=dfopen("http://127.0.0.1:8080/sign?body=".$preKey8801 ); 
		$preResult=HttpUtil::doHttpPost( $this->url."sign" ,['body'=>$preKey8801] );///
		if($preResult){//print_r($preResult);
			$result=json_decode($preResult,true);
			if($result['sign']&&$result['code']==1){//成功
				//println("8801密文：" + preResult );
				 ////拿到sing内容，这样写只是为了快
				$sign =   $result['sign'];
				//组装最终的请求报文
				$str =  rand();///随机流水号
				$firstPart = "<?xml version='1.0' encoding='GB2312'?><packet><head><transCode>8804</transCode>" .
		        "<signFlag>1</signFlag><packetID>" . $str . "</packetID><masterID>".$this->masterID."</masterID>";

				$timeStamp = "<timeStamp>" .date('Y-m-d H:i:s') . "</timeStamp></head><body>";

				$sign = "<signature>" . $sign ."</signature></body></packet>";

				$postBody = $firstPart . $timeStamp .$sign;
				$postBody =(strlen(  $postBody) +6  ). $postBody;  //计算出长度 +6 代表本身占用的长度
			 	echo $postBody = "00" .$postBody; //补齐6位
				//start
 
				$preResult =  HttpUtil::doHttpPost($this->url."request", ['body'=>$postBody] ); 
				if($preResult){ 
					$result=json_decode($preResult,true);print_r($result);
					if($result['code']==1){//成功
						$xml=xml2array($result['result']);print_r($xml);
					}else{
						return  false;
					}
					
				}
			}else{
				return  false;
			}
		}else{
			return  false;
		}

		exit; 

	   
	}   

	//5.5 8805 支付查询 
	function pay8805( $startp=1 ){

		 



		$preKey8801 = "<body>" .
                "<elecChequeNo></elecChequeNo><acctNo>92030078801200000311</acctNo>" .
                "<beginDate>20190921</beginDate><endDate>".date('Ymd')."</endDate><queryNumber>30</queryNumber><beginNumber>".$startp."</beginNumber>" .
                "<singleOrBatchFlag>0</singleOrBatchFlag>" .
                "</body>"; 
		
		 /////请求获得签名
		 /// 
        $preKey8801=   iconv("UTF-8", "GBK", $preKey8801)   ;  
 
		 
		//$preResult=dfopen("http://127.0.0.1:8080/sign?body=".$preKey8801 ); 
		$preResult=HttpUtil::doHttpPost( $this->url."sign" ,['body'=>$preKey8801] );///
		if($preResult){//print_r($preResult);
			$result=json_decode($preResult,true);
			if($result['sign']&&$result['code']==1){//成功
				//println("8801密文：" + preResult );
				 ////拿到sing内容，这样写只是为了快
				$sign =   $result['sign'];
				//组装最终的请求报文
				$str =  rand();///随机流水号
				$firstPart = "<?xml version='1.0' encoding='GB2312'?><packet><head><transCode>8805</transCode>" .
		        "<signFlag>1</signFlag><packetID>" . $str . "</packetID><masterID>".$this->masterID."</masterID>";

				$timeStamp = "<timeStamp>" .date('Y-m-d H:i:s') . "</timeStamp></head><body>";

				$sign = "<signature>" . $sign ."</signature></body></packet>";

				$postBody = $firstPart . $timeStamp .$sign;
				$postBody =(strlen(  $postBody) +6  ). $postBody;  //计算出长度 +6 代表本身占用的长度
			 	$postBody = "00" .$postBody; //补齐6位
				//start
 
				$preResult =  HttpUtil::doHttpPost($this->url."request", ['body'=>$postBody] ); 
				if($preResult){ 
					$result=json_decode($preResult,true);//print_r($result);
					@file_put_contents(APP_PATH.'../banklog/8805'.date('Y-m-d ').'.log', print_r($result,true),FILE_APPEND);  
					if($result['code']==1){//成功
						$xml=xml2array($result['result']);///print_r($xml);
						$totalCount=$xml['body']['sic']['body']['totalCount'];
						$count=$xml['body']['sic']['body']['count'];
						$datasr=['count'=>$count,'totalCount'=>$totalCount];
						if($count){
							$lists=$xml['body']['sic']['body']['lists']['list'];
							if(is_array($lists)){
								foreach ($lists as $key => $value) {
									$t=explode('|', $value['detailedContent']);
									$datalist[]=['elecChequeNo'=>$t[0],'acceptNo'=>$t[1],'serialNo'=>$t[2],'transDate'=>$t[3],'bespeakDate'=>$t[4],'PromiseDate'=>$t[5],'acctNo'=>$t[6],'acctName'=>$t[7],'payeeAcctNo'=>$t[8],'payeeName'=>$t[9],'payeeType'=>$t[10],'payeeBankName'=>$t[11],'amount'=>$t[12],'sysFlag'=>$t[13],'remitLocation'=>$t[14],'transStatus'=>$t[15],'seqNo'=>$t[16],];
									
								}
							}print_r($datalist);
							$datasr['code']= 1;$datasr['datalist']= $datalist;

						}else{
							$datasr['code']= 0;
						}
						return $datasr;

					}else{
						return  false;
					}
					
				}
			}else{
				return  false;
			}
		}else{
			return  false;
		}

		exit; 

	   
	}   

	//支付交易撤销
	function pay8809(  ){

		 



		$preKey8801 = "<body>" .
                "<acceptNo>PT19YQ0019703795</acceptNo>" .
                "<serialNo>1</serialNo>" .
                "</body>"; 
		
		 /////请求获得签名
		 /// 
                var_dump(iconv_get_encoding( ));
        $preKey8801=   iconv("UTF-8", "GBK", $preKey8801)   ;  
 var_dump( $preKey8801);
		 
		//$preResult=dfopen("http://127.0.0.1:8080/sign?body=".$preKey8801 ); 
		$preResult=HttpUtil::doHttpPost( $this->url."sign" ,['body'=>$preKey8801] );///
		if($preResult){//print_r($preResult);
			$result=json_decode($preResult,true);
			if($result['sign']&&$result['code']==1){//成功
				//println("8801密文：" + preResult );
				 ////拿到sing内容，这样写只是为了快
				$sign =   $result['sign'];
				//组装最终的请求报文
				$str =  rand();///随机流水号
				$firstPart = "<?xml version='1.0' encoding='GB2312'?><packet><head><transCode>8809</transCode>" .
		        "<signFlag>1</signFlag><packetID>" . $str . "</packetID><masterID>".$this->masterID."</masterID>";

				$timeStamp = "<timeStamp>" .date('Y-m-d H:i:s') . "</timeStamp></head><body>";

				$sign = "<signature>" . $sign ."</signature></body></packet>";

				$postBody = $firstPart . $timeStamp .$sign;
				$postBody =(strlen(  $postBody) +6  ). $postBody;  //计算出长度 +6 代表本身占用的长度
			 	$postBody = "00" .$postBody; //补齐6位
				//start
 
				$preResult =  HttpUtil::doHttpPost($this->url."request", ['body'=>$postBody] ); 
				if($preResult){ 
					$result=json_decode($preResult,true);//print_r($result);
					if($result['code']==1){//成功
						$xml=xml2array($result['result']);
						$transStatus=$xml['body']['sic']['body']['transStatus'];
						if($transStatus==9){print_r($xml);
							return true;
						}else{
							return false;
						}
						
					}else{
						return  false;
					}
					
				}
			}else{
				return  false;
			}
		}else{
			return  false;
		}

		exit; 

	   
	}   

	//4.1 4402账户余额查询
	function pay4402(  ){  

		$preKey8801 = "<body>" .
                "<lists name='acctList'><list><acctNo>92030078801200000311</acctNo></list>" .
                 
                "</lists>" .
                "</body>"; 
		
		 /////请求获得签名
		 ///  
		//$preResult=dfopen("http://127.0.0.1:8080/sign?body=".$preKey8801 ); 
		$preResult=HttpUtil::doHttpPost( $this->url."sign" ,['body'=>$preKey8801] );///
		if($preResult){ // print_r($preResult);
			 
			$result=json_decode($preResult,true);
			if($result['sign']&&$result['code']==1){//成功
				//println("8801密文：" + preResult );
				 ////拿到sing内容，这样写只是为了快
				$sign =   $result['sign'];
				//组装最终的请求报文
				$str =  rand();///随机流水号
				$firstPart = "<?xml version='1.0' encoding='GB2312'?><packet><head><transCode>4402</transCode>" .
		        "<signFlag>1</signFlag><packetID>" . $str . "</packetID><masterID>".$this->masterID."</masterID>";

				$timeStamp = "<timeStamp>" .date('Y-m-d H:i:s') . "</timeStamp></head><body>";

				$sign = "<signature>" . $sign ."</signature></body></packet>";

				$postBody = $firstPart . $timeStamp .$sign;
				$postBody =(strlen(  $postBody) +6  ). $postBody;  //计算出长度 +6 代表本身占用的长度
			 	$postBody = "00" .$postBody; //补齐6位
				//start
 
				$preResult =  HttpUtil::doHttpPost($this->url."request", ['body'=>$postBody] ); 
				if($preResult){ 
					@file_put_contents(APP_PATH.'../banklog/pay4402'.date('Y-m-d ').'.log',print_r($postBody,true). print_r($preResult,true),FILE_APPEND);  
					$result=json_decode($preResult,true);//print_r($result);
					if($result['code']==1){//成功
						$xml=xml2array($result['result']);//print_r($xml);
						$lists=$xml['body']['sic']['body']['lists']['list'];
						if(isset( $lists[0])){
							foreach ($lists as $key => $value) {
								$accont[]=$value;
							}
						}else{
							$accont[]=$lists;
						}
						$datasr['code']= 1;$datasr['datalist']= $accont;
						return $datasr;
					}else{
						return  false;
					}
					
				}
			}else{
				@file_put_contents(APP_PATH.'../banklog/pay4402'.date('Y-m-d ').'.log', print_r($preResult,true),FILE_APPEND);  
				return  false;
			}
		}else{
			return  false;
		}

		exit; 

	   
	}   

	//4.1 4403对公活期账户历史余额查询
	function pay4403( $arr ){ 


		$preKey8801 = "<body>" .
                "<acctNo>{$arr['acctNo']}</acctNo><beginDate>{$arr['beginDate']}</beginDate><endDate>{$arr['endDate']}</endDate>" .
                  
                "</body>"; 
		
		 /////请求获得签名
		 /// 
                  
		 
		//$preResult=dfopen("http://127.0.0.1:8080/sign?body=".$preKey8801 ); 
		$preResult=HttpUtil::doHttpPost( $this->url."sign" ,['body'=>$preKey8801] );///
		if($preResult){//print_r($preResult);
			
			$result=json_decode($preResult,true);
			if($result['sign']&&$result['code']==1){//成功
				//println("8801密文：" + preResult );
				 ////拿到sing内容，这样写只是为了快
				$sign =   $result['sign'];
				//组装最终的请求报文
				$str =  rand();///随机流水号
				$firstPart = "<?xml version='1.0' encoding='GB2312'?><packet><head><transCode>4403</transCode>" .
		        "<signFlag>1</signFlag><packetID>" . $str . "</packetID><masterID>".$this->masterID."</masterID>";

				$timeStamp = "<timeStamp>" .date('Y-m-d H:i:s') . "</timeStamp></head><body>";

				$sign = "<signature>" . $sign ."</signature></body></packet>";

				$postBody = $firstPart . $timeStamp .$sign;
				$postBody =(strlen(  $postBody) +6  ). $postBody;  //计算出长度 +6 代表本身占用的长度
			 	$postBody = "00" .$postBody; //补齐6位
				//start
 
				$preResult =  HttpUtil::doHttpPost($this->url."request", ['body'=>$postBody] ); 
				if($preResult){ 
					@file_put_contents(APP_PATH.'../banklog/pay4403'.date('Y-m-d ').'.log', print_r($preResult,true),FILE_APPEND);  
			
					$result=json_decode($preResult,true);//print_r($result);
					if($result['code']==1){//成功
						$xml=xml2array($result['result']);//print_r($xml);
						$lists=$xml['body']['sic']['body']['lists']['list'];
						if(isset( $lists[0])){
							foreach ($lists as $key => $value) {
								$accont[]=$value;
							}
						}else{
							$accont[]=$lists;
						}
						$datasr['code']= 1;$datasr['datalist']= $accont;
						return $datasr;
					}else{
						$datasr['code']= 0;
						$datasr['datalist']= [['tranFlag'=>$result['msg']]];
						return $datasr;
					}
					
				}
			}else{
				@file_put_contents(APP_PATH.'../banklog/pay4403'.date('Y-m-d ').'.log', print_r($preResult,true),FILE_APPEND);  
			
				return  false;
			}
		}else{
			return  false;
		}

		exit; 

	   
	}
	//4.1 4403对公活期账户历史余额查询
	function pay8924( $arr ){ 
		if(!$arr['beginNumber']){
			$arr['beginNumber']=1;
		}

		$preKey8801 = "<body>" .
                "<acctNo>{$arr['acctNo']}</acctNo><beginDate>{$arr['beginDate']}</beginDate><endDate>{$arr['endDate']}</endDate><queryNumber>50</queryNumber><beginNumber>{$arr['beginNumber']}</beginNumber>" .
                  
                "</body>"; 
		
		 /////请求获得签名
		 /// 
                  
		 
		//$preResult=dfopen("http://127.0.0.1:8080/sign?body=".$preKey8801 ); 
		$preResult=HttpUtil::doHttpPost( $this->url."sign" ,['body'=>$preKey8801] );///
		if($preResult){//print_r($preResult);
			$result=json_decode($preResult,true);
			if($result['sign']&&$result['code']==1){//成功
				//println("8801密文：" + preResult );
				 ////拿到sing内容，这样写只是为了快
				$sign =   $result['sign'];
				//组装最终的请求报文
				$str =  rand();///随机流水号
				$firstPart = "<?xml version='1.0' encoding='GB2312'?><packet><head><transCode>8924</transCode>" .
		        "<signFlag>1</signFlag><packetID>" . $str . "</packetID><masterID>".$this->masterID."</masterID>";

				$timeStamp = "<timeStamp>" .date('Y-m-d H:i:s') . "</timeStamp></head><body>";

				$sign = "<signature>" . $sign ."</signature></body></packet>";

				$postBody = $firstPart . $timeStamp .$sign;
				$postBody =(strlen(  $postBody) +6  ). $postBody;  //计算出长度 +6 代表本身占用的长度
			 	$postBody = "00" .$postBody; //补齐6位
				//start
 
				$preResult =  HttpUtil::doHttpPost($this->url."request", ['body'=>$postBody] ); 
				if($preResult){ 
					$result=json_decode($preResult,true); 
					@file_put_contents(APP_PATH.'../banklog/pay8924'.date('Y-m-d ').'.log',print_r($postBody,true). print_r($preResult,true),FILE_APPEND);  
			
					if($result['code']==1){//成功
						$xml=xml2array($result['result']); // print_r($xml);
						$datasr['totalCount']=$totalCount=$xml['body']['sic']['body']['totalCount'];
						$lists=$xml['body']['sic']['body']['lists']['list'];
						if(isset( $lists[0])){
							foreach ($lists as $key => $value) {
								$accont[]=$value;
							}
						}else{
							$accont[]=$lists;
						}
						$datasr['code']= 1;$datasr['datalist']= $accont;
						return $datasr;
					}else{
						$datasr['code']= 0;
						$datasr['datalist']= [['tranFlag'=>$result['msg']]];
						return $datasr;
					}
				}
			}else{
				return  false;
			}
		}else{
			return  false;
		}

		exit; 

	   
	}
	public static function getBytes($string) {
		$bytes = array();
		for($i = 0; $i < strlen($string); $i++){
		$bytes[] = ord($string[$i]);
		}
		return $bytes;
	} 



	function  addorder($orderdata){
		$head=array("Content-Type: application/x-www-form-urlencoded","Authorization:".$this->token);
		$this->url. '/api/v1/FengNiaoApi/SaveAddMemberInfo';
	 
		$r=HttpUtil::doHttpPost( $this->url. '/api/v1/FengNiaoApi/SaveAddMemberInfo' ,$orderdata,$head );
		if($r){
			$res=json_decode($r,true);var_dump($res['success']);
			if($res['success']=='true'){
				$orderid=$res['message'];var_dump($orderid);
			}
		}
		print_r($r);
	}   

	//获取订单状态
	function getorder($id){
	 
			$head=array("Content-Type: application/x-www-form-urlencoded","Authorization:".$this->token);
			
		 
			$r=HttpUtil::doHttpPost( $this->url. '/api/v1/FengNiaoApi/GetOrderStatus?orderNum='.$id ,[],$head,'get');print_r($r);
			$res=json_decode($r,true); 
 
			$citys =$res;

	  
		return $citys;
	}

} 
 
?>