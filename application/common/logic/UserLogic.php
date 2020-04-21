<?php
namespace app\common\logic;
use think\db;
use think\Loader;
use think\Request;
use app\common\model\Common;
class UserLogic extends Logic { 

	/*
	计算导入的提现处理

	$fromuid 粉丝 (来源)
	$uid  主播 ( 我的id)
	$money 金钱元
	*/
	static function Commission( ){
	 	///$Common=new   Common;
		$Common=new  \app\common\model\Common;$Common=new  \app\common\model\Common;

	 	$pfbanklogic= new \app\common\logic\pfbanklogic;
	 	$chatuser_income=db('account')->where('transStatus','0')->select();
	 	if($chatuser_income){
	 		$datas['elecChequeNo']=date('md').createCheckCode(2,0);
        	// Db::startTrans();
	 		foreach ($chatuser_income as $key => $value) {
	 			
	 			$money= $value['amount'] ;////  提成 
	 			if($value['amount']){
	 				$tmp['amount']=$value['amount'];
	 				///明细序号|是否浦发账户|收付款人对公对私标志|银行卡卡类型|对方账号|对方账户名|证件类型|证件号码|对手行行号|对手行行名|支付行号|币种|金额|手机号|企业流水号|备用信息|企业分支机构|摘要|备注|备用1|备用2|备用3
	 				$detail[0]=$value['id'];//明细序号

	 				if(strpos($value['payeeBankName'], '浦发')!==false){
	 					$ispuf=0;
	 				}else{
	 					$ispuf=1;
	 				}


	 				$detail[1]= $ispuf ;//是否浦发账户
	 				$detail[2]=1;//收付款人对公对私标志0:对公 1:对私
	 				$detail[3]= 1;//银行卡卡类型
	 				$detail[4]=$value['payeeAcctNo'];//对方账号
	 				$detail[5]=$value['payeeName'];//对方账户名
	 				$detail[6]='';//证件类型
	 				$detail[7]='';//证件号码

	 				$payeeBankName=substr($value['payeeBankName'], 0,strpos($value['payeeBankName'],'银行'));

	 				var_dump($payeeBankName);
	 				$bankname=db('bankname')->where('name','like','%'.$payeeBankName.'%')->find();
	 				print_r($bankname);
	 				if($bankname){
	 					$bankcode =$bankname['code'];
	 				}else{
	 					$bankcode ='';
	 				}
	 				$detail[8]=$bankcode ;//对手行行号
	 				$detail[9]=$value['payeeBankName'];//对手行行名payeeBankName
	 				$detail[10]='';//支付行号
	 				$detail[11]='';//币种
	 				$detail[12]=$value['amount'];//金额
	 				$detail[13]='';//手机号
	 				$detail[14]='';//企业流水号
	 				$detail[15]='';//备用信息
	 				$detail[16]='';//企业分支机构
	 				$detail[17]='';//摘要
	 				$detail[18]='';//备注
	 				$detail[19]='';//备用1
	 				$detail[20]='';//备用2
	 				$detail[21]='';//备用3

	 				$tmp['detailedContent']=implode('|',$detail);
	 				$list[]=$tmp;
	 				$uids[]= $value['id']; 
	 				
	 			}
	 			
	 		}
	 		$datas['list']=$list;
	 		$rdatas=$pfbanklogic->payAQ52($datas);
	 		if($rdatas){
				db('account')->where('id','in',$uids)->update(['batchNo'=>$datas['elecChequeNo'],'transStatus' => 1,'acceptNo'=>$rdatas['handleSeqNo']]);
			}
	 		if($uids){
	 			
	 			

	 			//$r3=db('account')->where('id','in',$uids)->update(['transStatus' => 1]);
	 		}
	 		
				 		
			// var_dump($r);var_dump($r2);var_dump($r3);
			// 	 			if($r!==false &&$r3!==false){
			// 	 				Db::commit(); 
			// 	 			}else{
			// 	 				Db::rollback();
			// 	 			}


	 	} 
	 	//   $datas1['handleSeqNo']='05022019101810296939';
			// $rdatas=$pfbanklogic->payAQ54($datas1);

 		// 	 print_r($rdatas);
// 			$datas1['handleSeqNo']='05022019101710295058';
// 			$rdatas=$pfbanklogic->payAQ54($datas1);
	 	 
// print_r($rdatas);
	}



	/*
	*主播等级计算公式
	11-14
	*/
	static function levelfunc( ){
		//基数 2元/分
		$basic=2;
		//幂
		$mi=0;//0.5;
		//单个主播包天 算法参数
		$daycard_parm=12;

		//单个主播包月  算法参数
		$monthcard_parm=11;


		$levtimes[0]=0;
		for($i=1;$i<=100 ;$i++){
			$levtimes[$i]=$levtimes[$i-1]+($i*10);
			$data[$i]['times']=$levtimes[$i]*60;// 需要升级条件：累计聊天时长（秒）
			if($i==1){//单价 分/秒
				$unit_price= bcdiv ($basic*100,60,4) ;
			}else{
				$unit_price= bcdiv(pow($i , $mi)*$basic*100 ,60,4) ;
			}

			$data[$i]['daycard']=bcdiv($unit_price*60*$daycard_parm ,100,2);//单个主播包天  元
			$data[$i]['monthcard']=round($data[$i]['daycard']*$monthcard_parm );//单个主播包月 元

			$data[$i]['unit_price']=round($unit_price,2);
			
		} 
		return $data;
	}

	/*/主播根据当前级别获得升级进度条件
	$level 当前级别
	$gettimes 当前已获得时间
	*/
 
	function  curlevel_info($level,$gettimes){
		$levelarr=self::levelfunc( );
		$data['curunit_price']=bcdiv($levelarr[$level]['unit_price'],100,3);//////bcdiv(bcmul($levelarr[$level]['unit_price'],60,2),100,2);//当前单价  分/秒 转换为  元/分
		$data['upgrade_times']=$levelarr[$level]['times'];
		$data['upgrade_pre']=bcmul (bcdiv($gettimes,$data['upgrade_times'],4),100,2);
		$data['nextunit_price']=bcdiv($levelarr[$level+1]['unit_price'],100,3);/////bcdiv(bcmul($levelarr[$level+1]['unit_price'],60,2),100,2); //下一级单价  分/秒 转换为  元/分

		

		return $data;
	}

	/*
	*粉丝等级计算公式 $money 单位元
	11-14
	*/
	static function fans_levelfunc( $money){
		 
		//幂
		$mi=2;  
		$data[1]=0;$data[2]=1;
		if($money>=$data[2]) { 
			$level=2;
		}

		for($i=3;$i<=100 ;$i++){ 
			$modi=bcmod($i-1,20);//求摸  
			if($modi==0){ //20级 一跳
				///echo " i".$i.'<br>';

			 ///echo " modi".$modi.'<br>';
				$mi+=0.1;//echo " mi".$mi.'<br>';
			}
			$data[$i]=pow($i , $mi) ;// 需要升级条件：充值总金额满足（元）
			if($money>=$data[$i]) {

				$level=$i;
			}else{
				///echo "money".$money."data[$i]".$data[$i].'<br>';
				break;

			}
		} 
 
		return $level;
	}

	///  粉丝分享获得奖励时间
	function share_gettimes($uid){
		$ini_time=[60,120];//第一个 送1，2分钟
		$count=db('chatuser')->where('share_uid',$uid)->count();//第几个点击人
		$count=pow(2,$count );

		$randtime=rand(bcdiv($ini_time[0],$count),bcdiv($ini_time[1],$count));
		$randtime=$randtime<=1?1:$randtime;

		return  10;
	}
	///  粉丝分享当天 有多少时间  
	function get_daysharetimes($uid){
		$day=date('Y-m-d');
		$count=db('chatuser')->where(['share_uid'=>$uid,'regtime'=>['BETWEEN',[$day,$day.' 23:59:59']]])->count();//今天分享第几个点击人
		///每人送10秒
		return $count*10;
	}

	/*
	记录主播收入记录

	$fromuid 粉丝 (来源)
	$uid  主播 ( 我的id)
	$money 金钱元
	*/
	static function useraccount($fromuid,$uid,$money,$types=0,$acc_money=0){
	 
		db('account_logs')->insert( [
			'fromuid'=>$fromuid,
		    'uid'=>$uid,
		    'money'=>$money,
		    'types'=>$types,
		    'acc_money'=>$acc_money ]
		    );
	}

	/**
	购买记录，累计到个人用户
	**/
	static function sum_account($uids,$price_arr,$log=false ){
	 
		$db=db('chatuser');
		if($price_arr['account'] ){
		
			if(strpos($price_arr['account'],'-')!==false){////echo 'sssss';var_dump(strpos($price_arr['account'],'-'));
				$db=$db->dec('account',abs($price_arr['account']));
			}else{
				$db=$db->inc('account',$price_arr['account']);
				$db->inc('total_income',$price_arr['account']); 
			}  
		}
		if($price_arr['acc_account'] ){
		
			if(strpos($price_arr['acc_account'],'-')!==false){
				$db=$db->dec('acc_account',abs($price_arr['acc_account']));
			}else{
				$db=$db->inc('acc_account',$price_arr['acc_account']);
			}  
		} 
		 
		$db->where('id',$uids)->update(); 
	  
		
	}

	/**
	天使结算方式：每月结算一次
	*/
	function  angel_card(){
		/*全站天使获得金额=主播（当月天使聊天时长）÷当月天使聊天总时长×本月开通天使金额（最后得出的数字要乘0.6才是主播与公会的收入）

		select v.*, totaltiems   ,  v.times/totaltiems*price   from  v3_angel_chattime_views as v 
INNER JOIN  (select  sum(times) as totaltiems ,card_id from  v3_angel_chattime_views    where  end_date='2018-12-08'  GROUP BY   card_id )  t on t.card_id=v.card_id 

 where v.end_date='2018-12-08 and  v.status=0'  

*/
		$nowday=date('Y-m-d');
		$lastweek=date('Y-m-d',strtotime("-1 week"));
		$wheres['type']='1';
		$wheres['end_date']=['BETWEEN',[$lastweek,$nowday]]; 
		$wheres['status']='0';
		$subQuery = db('angel_chattime_views')->field('sum(times) as totaltiems ,card_id') ->where($wheres)->group('card_id')->buildSql();
		
		$list=db('angel_chattime_views')->alias('v')->field(' v.*, totaltiems   ,  v.times/totaltiems*price as money ')->join([$subQuery=>'t'],'t.card_id=v.card_id ' ) ->where($wheres)->select();
		 
		file_put_contents(APP_PATH.'../angel_cardlogs/'.date('Y-m-d ').'.log', print_r($list,true),FILE_APPEND);
		if(is_array($list)){
			foreach ($list as $key => $value) {
				echo $money=bcmul($value['money'],1,2);
				$from_uid=$value['from_uid'];
				$d['acc_account']=$d['account']=$money*100;//分
				self::sum_account($from_uid,$d);

				self::useraccount($value['uid'],$from_uid,$money,1);
				db('chatuser_angel_chattime')->where('id',$value['id'])->update(['status'=>1]);

			}
		}

	}


}
