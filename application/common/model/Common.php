<?php
namespace app\common\model;
use think\Db;
use think\Loader;
class Common  
{
	/*
	保存数据到表
	$data数据
	$table 表名
	$id update的 情况需要 id
	*/
	public function Add_data($data,$table,$id=null ){
		if (!$id)//插入
		{
			Db::name($table)->insert($data,true);
			$insertid= Db::name($table)->getLastInsID();
		}else{//更新 
			Db::name($table)->update($data);
			$insertid=$id;
		}
		return $insertid;
	}

	/*
	type :'basic';普通标准订单算法， picc 算法
	*/
	public function  getpay_code($table='order',$pay_id='' ,$field='sn',$arr=['sntype'=>'order','snnum'=>4]){//获取订单唯一码
	
		if($pay_id){ 
			$getreturn =db($table)->where([$field=>$pay_id])->find();
			return $getreturn ;

		}else{
 
			 
			$randid = strtolower(createCheckCode($arr['snnum'],0 )  );
			if($arr['sntype']=='order'){
				$scode=time().$randid;
			}else if($arr['sntype']=='account'){
				$scode=date('Ymd').$randid;
			}else{
				$scode= $randid;
			}
			
			  
			 
			$getreturn =db($table)->field($field)->where([$field=>$pay_id])->find();   
			if(is_array($getreturn)){
				$scode=$this->getpay_code($table ,''  );
			}
			return $scode;

		}
	}
	///把查詢列表整合成 主索引 為key
	function selectindex($datas,$pkid='id'){
		$rdatas=[];
		if(is_array($datas)){
			foreach ($datas as   $value) {
				$rdatas[$value[$pkid]]=$value;
			}
			return $rdatas;
		}
		return $datas;

	}


	//图片加到pdf
	//$xy  数组 [x,y,w]
	function pdfsign($pdffile,$dpdf,$image,$xy){
		$pdf = new \setasign\Fpdi\Fpdi();
		// 載入現在 PDF 檔案
		$page_count = $pdf->setSourceFile($pdffile);
		// 匯入現在 PDF 檔案的第一頁
		for ($pageNo = 1; $pageNo <= $page_count; $pageNo++) {  
            // 获取原始pdf的第pageNo页内容
            $templateId = $pdf->importPage($pageNo);  
            // 获取该页pdf内容的宽高
            $size = $pdf->getTemplateSize($templateId);  
            // 创建一个新的pdf空白页 orientation L 是横版（宽比高大） P是竖版（宽比高小）
            $pdf->AddPage($size['orientation'], array($size['width'], $size['height']));
            // 在新加的空白页上插入开始时获取的pdf内容
            $pdf->useTemplate($templateId);
           
        }   
		// $pdf->addPage();
		// $pdf->useTemplate($tpl);
		// 放置圖形
		$pdf->Image($image,$xy['x']  , $xy['y'], $xy['w']);

		
		 
		if (false === checkPath(dirname($dpdf))) {
            return false;
        }
		// 輸出成本地端 PDF 檔
		$pdf->output($dpdf, "F");
		$pdf->close(); 
	} 
}
