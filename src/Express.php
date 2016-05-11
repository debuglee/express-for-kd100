<?php namespace Debuglee\Express;


use DB;

/**
* Express
*/
class Express
{
	
	public static function sayHolle()
	{
		 return config('express.message');
	}


	/**
	 * 订阅请求
	 * 订阅请求是指由贵公司发起的web请求，用于声明贵公司需要我方帮忙跟踪某个快递公司的某个运单号，一个单号订阅成功一次即可，快递100收到订阅后会对该单号进行监控与推送。
	 * 
	 * @param  string $expressCompany 快递公司编码 例如:yuantong
	 * @param  string $expressNumber  快递单号
	 * @param  string $expressForm    出发地城市，省-市-区，非必填，填了有助于提升签收状态的判断的准确率，请尽量提供
	 * @param  string $expressTo      目的地城市，省-市-区，非必填，填了有助于提升签收状态的判断的准确率，且到达目的地后会加大监控频率，请尽量提供
	 * @param  string $userMobile     收件人的手机号，提供后快递100会向该手机号对应的QQ号推送【发货】、【同城派件】、【签收】三种信息，非必填，只能填写一个
	 * @param  string $seller         寄件商家的名称，如果是平台方，则直接提供平台方名称，如果是平台上的商家，则提供商家名称。
	 * @param  string $commodity      寄给收件人的商品名
	 * @param  string $isCheckCompany 是否检查快递公司编码
	 * @return bool                   成功返回true  失败false                  
	 */
	public function subscribeExpressInfo($expressCompany, $expressNumber, $expressForm='', $expressTo='', $userMobile='', $seller='', $commodity='', $isCheckCompany=false)
	{
		if (empty($expressCompany) || empty($expressNumber)) {
			return false;
		}

		$paramArray = array();
		$paramArray['company'] = $expressCompany;
		$paramArray['number']  = $expressNumber;
		$paramArray['from']	   = $expressForm;
		$paramArray['to']	   = $expressTo;
		$paramArray['key']	   = config('express.AUTH_KEY');
		$paramArray['parameters']['callbackurl'] = config('express.CALL_BACK_URL');
		$paramArray['parameters']['salt'] = self::hashSalt($expressNumber);
		$paramArray['parameters']['mobiletelephone'] = $userMobile;
		$paramArray['parameters']['seller'] = $seller;
		$paramArray['parameters']['commodity'] = $commodity;

		// 发起订阅请求

		$getExpressData = self::getExpressData($paramArray);

		return true;
	}


	/**
	 * 发送订阅请求
	 * @param  array $params 订阅参数
	 * @return mix 成功返回数据，失败返回false
	 */
	private function getExpressData($params){

		if (empty($params)) {
			return false;
		}

 		$url = config('express.REQUEST_RUL');

 		$postData = array();
 		$postData['schema'] = 'json';
 		$postData['param']  = json_encode($params);

 		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			// post数据
			curl_setopt($ch, CURLOPT_POST, 1);
			// post的变量
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
			$output = curl_exec($ch);
			curl_close($ch);
 		} catch (Exception $e) {
 			return false;
 		}

 		$result = json_decode($output);

		if (empty($result) || !isset($result['returnCode'])) {
			return false;
		}

		if ($result['returnCode'] != '200') {
			return false;
		}

		return $result;
	}



	public function calllBack($param, $sign = ''){
		if (empty($param)) {
			return false;
		}

		$expressData = json_decode($param);

		if (empty($expressData) || !isset($expressData)) {
			return false;
		}

		// 取得快递信息,保存数据

		// 查询对应单号
		if (!empty($expressData->lastResult->nu) || isset($expressData->lastResult->nu)) {
			$expressNumber = $expressData->lastResult->nu;
		}

		// 校验快递数据
		
		$salt = self::hashSalt($expressNumber);
		$checkSign = md5($param . $salt);

		if ($sign != $checkSign) {
			return false;
		}


		$results = DB::select('select * from express where express_number = :express_number', ['express_number' => $expressNumber]);
		// 如果无相关物流信息写入新订阅信息
		
		if (empty($results)) {
			$expressInfo = array();
			$expressInfo['order_id'] = '';
			$expressInfo['api_status'] = $expressData->status;
			$expressInfo['express_company'] = $expressData->lastResult->com;
			$expressInfo['express_number'] = $expressData->lastResult->nu;
			$expressInfo['ischeck_packer'] = $expressData->lastResult->ischeck;
			$expressInfo['express_state'] = $expressData->lastResult->state;
	
			// 转码数据
			$expressInfo['express_data'] = serialize($expressData->lastResult);
			$expressInfo['created_at'] = date('Y-m-d H:i:s', time());
			$expressInfo['updated_at'] = date('Y-m-d H:i:s', time());
	
			$insert = DB::table('express')->insert($expressInfo);
		} else {

			$expressInfo = array();
			$expressInfo['express_number'] = $expressData->lastResult->nu;
			$expressInfo['api_status'] = $expressData->status;
			$expressInfo['ischeck_packer'] = $expressData->lastResult->ischeck;
			$expressInfo['express_company'] = $expressData->lastResult->com;
			$expressInfo['express_data'] = serialize($expressData->lastResult);
			$expressInfo['updated_at'] = date('Y-m-d H:i:s', time());

			$update = DB::table('express')
			->where(array('express_number' => $expressInfo['express_number']))
			->update($expressInfo);
		}



		$response = array();
		$response['result'] = 'true';
		$response['returnCode'] = '200';
		$response['message'] = '提交成功';

		return json_encode($response);
	}

	/**
	 * 生成salt串
	 * @param  string $expressNumber 快递单号
	 * @return mix         成功返回salt串，失败返回false
	 */
	private function hashSalt($expressNumber){
		$salt = config('express.SALT');
		return md5($expressNumber . $salt);
	}
}