<?php
class Queue{

	static function arrive($phone){
		// send msg
		$sql = "UPDATE queue SET status = 'arrived' WHERE phone = $phone AND status = 'smsed'";
		$r = DB::sql($sql);
		$rid = F3::get("COOKIE.se_user_admin");
		$name = Restaurant::getName($rid);
		$msg = new PHPFetion(F3::get('Fetionphone'), F3::get('Fetionpasswd'));
		$msg->send($phone, "您的排队已被<$name>确认为到达, 感谢您的参与, 祝您用餐愉快! ");
	}

	static function notify($phone){
		// send msg
		$rid = F3::get("COOKIE.se_user_admin");
		$name = Restaurant::getName($rid);
		$addr = Restaurant::getAddr($rid);
		$msg = new PHPFetion(F3::get('Fetionphone'), F3::get('Fetionpasswd'));
		$msg->send($phone, "感谢您耐心参加<$name>的排队，请您与30分钟内到达<$addr>用餐，逾期可能会被商家取消. ");
		$rphone = Restaurant::getphone($rid);
		$msg->send($rphone, "吃货<$phone>已收到短信, 将在30分钟内速速赶来, 还不快把好酒好菜备好!");

		$sql = "UPDATE queue SET status = 'smsed' WHERE phone = $phone AND status = 'queuing'";
		$r = DB::sql($sql);
	}

	static function notice_rest_of_canceled($queue_line){
		// send msg
		$rid = $queue_line[0]['rid'];
		$rphone = Restaurant::getphone($rid);
		$uphone = $queue_line[0]['phone'];

		$msg = new PHPFetion(F3::get('Fetionphone'), F3::get('Fetionpasswd'));
		$msg->send($rphone, "吃货<$uphone>已取消排队, 敬请注意!");
	}

	static function getAll($rid){
		$a = array();
		$sql = "SELECT * FROM `table` WHERE rid = $rid ORDER BY capacity DESC";
		//echo $sql;
		$r = DB::sql($sql);
		//Code::dump($r);

		foreach($r as $v){
			$c = $v['capacity'];
			$sql = "SELECT * FROM queue WHERE rid = $rid AND `table` = $c
				AND (status = 'queuing' OR status = 'smsed') ORDER BY time ASC";
			$rs = DB::sql($sql);
			//Code::dump($rs);
			$tmp = array();
			foreach($rs as $k => $v){
				$tmp[$k] = array();
				$tmp[$k]['num'] = $v['num'];
				$tmp[$k]['status'] = $v['status'];
				$tmp[$k]['phone'] = $v['phone'];
				$tmp[$k]['time'] = date("H:i:s", $v['time']);
			}
			$a[] = array(
				'capacity' => $c,
				'customer' => $tmp
			);
		}
		return $a;
	}

	static function addItem($rid, $phone, $num){
		if(User::exist("phone", $phone, "queue", "status = 'queuing'"))
			return -2;
		$time = time();
		$table = Table::getTable($rid, $num);
		if($table == -1)
			return -1;
		
		$sql = "INSERT INTO queue VALUES 
			('', $rid, '$table', '$phone', '$num', '$time', '', '', 'queuing')";
		//echo $sql;
		$r = DB::sql($sql);
		return 1;
	}

	static function getNextCustomer(){
		$rid = F3::get("COOKIE.se_user_admin");
		if($rid == 0)
			return false;
		$rs = DB::sql('SELECT phone FROM queue WHERE rid = :rid AND status = "queuing" ORDER BY time ASC', 
			array(':rid' => $rid)
		);
		//Code::dump($rs);
		$phone = $rs[0]['phone'];
	}

	static function getNum($rid){
		$rs = DB::sql('SELECT COUNT(*) FROM queue WHERE rid = :rid AND status = "queuing"', 
			array(':rid' => $rid)
		);
		return $rs[0]["COUNT(*)"];
	}
	//add
	static function getUserStatus($phone){
		$max_t=DB::sql('SELECT MAX(time) as time FROM queue WHERE phone = :phone',array(':phone'=>$phone));
		if($max_t==-1)
			return false;
		$max_time=$max_t[0]['time'];
		$s=DB::sql('SELECT * FROM queue WHERE time = :max_time and phone =:phone',array(':max_time'=>$max_time,':phone'=>$phone));
		return $s;
	}
	static function cancelBook($qid){
		$sql = "UPDATE queue SET status = 'quited' WHERE qid = $qid";
		$r = DB::sql($sql);
	}
	//add
}
