<?php
namespace app\common\logic;
include_once("fnapi.config.php");
/*********************************************************
*文件名：  fnapilogic.php
*作者：狐狸<foxis@qq.com>
*创建时间：  2010年6月12日
*修改时间：
*功能描述： 话题模块相关的数据库操作
*使用方法：

******************************************************/
use tencentai\sdk\HttpUtil; 
use think\Cache;
class fnapilogic extends Logic
{
 
    
    var $token;

    
    var $_cache;
 

 
    function __construct($base = null)
    {
        $this->client_id=fnapiConf_pub::client_id;
        $this->client_secret=fnapiConf_pub::clientSecret;
        $this->url=fnapiConf_pub::JS_API_CALL_URL;

        $this->get_token();
    }

    function get_token(){///授权
		if(($gettoken=Cache('token' ))===false)
		{
			$data=array(  
				'grant_type'=>'client_credentials',
				'client_id'=>$this->client_id ,
				'client_secret'=>$this->client_secret ,
				 
				);
 ///echo $this->url.$this->opercenter.'/auth/v1/oauth2/token';
			$r=HttpUtil::doHttpPost( $this->url. '/connect/token', ( $data));
			if($r){
				$res=json_decode($r,true);
				if($res['access_token'] ){
					$res['Authorization']='Bearer '.$res['access_token'];
					cache('token',$res,7000);
					$gettoken =$res;
					
				}else{
					$this->get_token();
				} 
			}
			

			 
		}
	  
		$this->token= $gettoken['Authorization'];
 
	}
	//$ex=true  读缓存  false 写缓存
	function getcity($ex=true){
		if($ex==true){


			if(($citys=Cache('citys' ))===false)
			{
				$head=array("Content-Type: application/x-www-form-urlencoded","Authorization:".$this->token);
				 
				$r=HttpUtil::doHttpPost( $this->url. '/api/v1/FengNiaoApi/GetAllCityList' ,[],$head,'get');
				$res=json_decode($r,true); 
				cache('citys',$res,0);
				$citys =$res;

			}
		}else{
			$head=array("Content-Type: application/x-www-form-urlencoded","Authorization:".$this->token);
				 
			$r=HttpUtil::doHttpPost( $this->url. '/api/v1/FengNiaoApi/GetAllCityList' ,[],$head,'get');
			$res=json_decode($r,true); 
			cache('citys',$res,0);
			$citys =$res;

		}
		//echo '<br>提交增员订单';
		// $orderdata=['cityId'=>2,'name'=>'panling','sex'=>'男','idNum'=>'310110197906095053','socialBase'=>3333,'socialStartMonth'=>3,'socialAddMemberType'=>'调入','isAccumulationPay'=>'false'];

		// $this->addorder($orderdata);

		//$this->getorder(45);
		return $citys;
	}
	//获取城市政策  写缓存
	function GetCityPolicy( $cityId){
		 
		$head=array("Content-Type: application/x-www-form-urlencoded","Authorization:".$this->token);
			 
		$r=HttpUtil::doHttpPost( $this->url. '/api/v1/FengNiaoApi/GetCityPolicy?cityId='.$cityId ,[],$head,'get');
		$citys=json_decode($r,true); 
 
		 

	  
		return $citys;
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