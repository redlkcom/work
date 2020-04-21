<?php

/*********************************************************
*文件名：  鸿 愿 健 康 
*修改时间：
*功能描述： 接口
*使用方法：

******************************************************/
require_once('hongyjktj.config.php'); 


class hongyjktjapilogic
{

    
    var $DatabaseHandler;
	 
    var $Config;
    
    
    var $_cache; 
 
	 
	const ERROR = 'ERROR'; const WRANG = 'WRANG'; 

    function __construct($base = null)
    {
    	global   $paykey, $url ,$PASSWORD ;
   
        if ($base)
        {
            $this->DatabaseHandler = $base->DatabaseHandler;

            $this->Config = $base->Config;
        }
        else
        {
            $this->DatabaseHandler = &Obj::registry("DatabaseHandler");

            $this->Config = &Obj::registry("config");
        } 
        $this->paykey=$paykey;
        $this->url=$url;
        $this->PASSWORD=$PASSWORD; 

		$kdname=$this->Config ['express_com'];
		 
    }  
 
    function getsign(){//签名拼装
  //   	ksort($this->data);//print_r($this->data);
  //   	$arg='';
  //   	while (list ($key, $val) = each ($this->data)) {
		// 	if($key == "sign"  || $val == "")continue;
		// 	else	$arg.=$key.$val;
		// } 
		/// $this->PASSWORD.$this->paykey.$this->data['timestamp'];
    	$sing=strtoupper (md5($this->PASSWORD.$this->paykey.$this->data['timestamp']));
    	return  $sing;
    }  
 
	
	function  pushOrderInfo( $id ){//推送用户套餐信息
		 
		ini_set('max_execution_time',0);
  		set_time_limit (0); 
		$this->action='getGroupByGroupIds';
		Load::logic('product');
		$TopicLogic = new Product($this);  
		$this->data = $TopicLogic->Get($id,   '*',   '',   TABLE_PREFIX."hongyuanorder",'thirdId');
 
		// echo $this->data['mealList']=json_encode(array(array('packageCode'=>'HY001909211','num'=>1),array('packageCode'=>'HY001909212','num'=>2)));
		if($this->data){
			$this->data['mealList']=json_decode( $this->data['mealList'],true);
			
			//////////获取api数据//////////////////////////////////////////////////
		 	$this->data['timestamp']=date('YmdHis');
		 	$this->data['key']=$this->paykey;
			$this->data['sign']=$this->getsign();
			echo $url=$this->url. 'hongyuan_api/pushOrderInfo';
			$js=json_encode( ($this->data)); print_r($js);
			$r=post_request( $url, $js ,array("content-type: application/json; "));
		 
			$rst=json_decode( $r['rst'],true );
			@file_put_contents(ROOT_PATH.'log/'.'hongyuan'.date('Y-m-d').'.log', $url."  paramsData=>". print_r($r,true)  ."\t\n" ,FILE_APPEND); print_r($rst);exit;
			if($rst['code']=='200'){// 
				return  array('code'=>true);

			}else{//error
				if($rst['message']){
					$error.= $rst['message'].' ';
				}
				if($rst['data']){
					foreach($rst['data'] as $v){
						$error.= $v['message'].' ';
					}
				}
				
				return  array('code'=>0,'message'=>$error);
			}
				     
			
		} 
 
		
		//return $this->data;
		
	}
 	function  orderLogin( $id ){//免密登录
		 
		ini_set('max_execution_time',0);
  		set_time_limit (0);  
		Load::logic('product');
		$TopicLogic = new Product($this);  
		$hongyuanorder = $TopicLogic->Get($id,   '*',   '',   TABLE_PREFIX."hongyuanorder",'thirdId');
 
		// echo $this->data['mealList']=json_encode(array(array('packageCode'=>'HY001909211','num'=>1),array('packageCode'=>'HY001909212','num'=>2)));
		if($hongyuanorder){
			 
			
			//////////获取api数据//////////////////////////////////////////////////
			$this->data['thirdId']=$id;
		 	$this->data['timestamp']=date('YmdHis');
		 	$this->data['key']=$this->paykey;
			$this->data['sign']=$this->getsign();
			echo $url=$this->url. 'hongyuan_api/orderLogin';
			$js=json_encode( ($this->data)); print_r($js);
			$r=post_request( $url, $js ,array("content-type: application/json; "));
		 
			$rst=json_decode( $r['rst'],true );
			@file_put_contents(ROOT_PATH.'log/'.'hongyuanlogin'.date('Y-m-d').'.log', $url."  paramsData=>". print_r($r,true)  ."\t\n" ,FILE_APPEND);//
		 
			if($rst['code']=='200'){// 
				return  array('code'=>true,'url'=>$rst['data']);

			}else{//error
				if($rst['message']){
					$error.= $rst['message'].' ';
				}
				if($rst['data']){
					foreach($rst['data'] as $v){
						$error.= $v['message'].' ';
					}
				}
				
				return  array('code'=>0,'message'=>$error);
			}
				     
			
		} 
		return  array('code'=>0,'message'=>'没有该订单');
 
		
		//return $this->data;
		
	}

	function  queryReportUrl( $id ){//查询体检报告 PDF
		 
		ini_set('max_execution_time',0);
  		set_time_limit (0);  
		Load::logic('product');
		$TopicLogic = new Product($this);  
		$hongyuanorder = $TopicLogic->Get($id,   '*',   '',   TABLE_PREFIX."hongyuan_data" );

		// echo $this->data['mealList']=json_encode(array(array('packageCode'=>'HY001909211','num'=>1),array('packageCode'=>'HY001909212','num'=>2)));
		if($hongyuanorder ){
			 
			
			//////////获取api数据//////////////////////////////////////////////////
			$this->data['orderCode']=$hongyuanorder['ordercode'];
		 	$this->data['timestamp']=date('YmdHis');
		 	$this->data['key']=$this->paykey;
			$this->data['sign']=$this->getsign();
			echo $url=$this->url. 'hongyuan_api/queryReportUrl';
			$js=json_encode( ($this->data)); print_r($js);
			$r=post_request( $url, $js ,array("content-type: application/json; "));
		  print_r($r);
			$rst=json_decode( $r['rst'],true ); 
			@file_put_contents(ROOT_PATH.'log/'.'hongyuanpdf'.date('Y-m-d').'.log', $url."  paramsData=>". print_r($r,true)  ."\t\n" ,FILE_APPEND);//
		 
			if($rst['code']=='200'){// 
				return  array('code'=>true,'url'=>$rst['data']);

			}else{//error
				if($rst['message']){
					$error.= $rst['message'].' ';
				}
				if($rst['data']){
					foreach($rst['data'] as $v){
						$error.= $v['message'].' ';
					}
				}
				
				return  array('code'=>0,'message'=>$error);
			}
				     
			
		} 
		return  array('code'=>0,'message'=>'没有该订单');
 
		
		//return $this->data;
		
	}


	function  get_orderCode(){
		$request = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");

		$rst=json_decode( $request,true ); 
 
		$this->data['timestamp']=$rst['timestamp'];
	 	$this->data['key']=$this->paykey;
		$sign=$this->getsign();
		if($sign==$rst['sign']){
			if($rst['orderCode']){
				if($rst['thirdId']){//echo $rst['thirdId'];
					Load::logic('product');
					$TopicLogic = new Product($this);  
					$hongyuanorder = $TopicLogic->Get($rst['thirdId'],   '*',   '',   TABLE_PREFIX."hongyuanorder",'thirdId'); 
					if($hongyuanorder){
						$this->DatabaseHandler->SetTable(TABLE_PREFIX ."hongyuanorder"); 
						$udata=array('ordercode'=>trim($rst['orderCode']));
						$udata['thirdId']=$rst['thirdId'];
						$udata['appointmentDate']=trim($rst['appointmentDate']);
						$udata['packageCode']=trim($rst['packageCode']);
						$udata['appointmentOrg']=trim($rst['appointmentOrg']);
						$udata['appointmentOrgAddress']=trim($rst['appointmentOrgAddress']);

						$TopicLogic->Add_data($udata,"hongyuan_data");
						$return =array('code'=>200,'message'=>'');
					}else{
						$return =array('code'=>400,'message'=>'没有该订单');
					}
				}else{
					$return =array('code'=>400,'message'=>'thirdId不得为空');
				}
			}else{
				$return =array('code'=>400,'message'=>'订单号不得为空');
			} 
			
		}else{
			$return =array('code'=>400,'message'=>'签名错误');
		}
		return  json_encode($return);
	}


} 
 

?>