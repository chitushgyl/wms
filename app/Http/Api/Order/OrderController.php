<?php
namespace App\Http\Api\Order;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopOrderCarriage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\ComputeController as Compute;
use App\Http\Api\Pay\NotifyController as Notify;
// use Illuminate\Support\Facades\Schema;

class OrderController extends Controller{
    /**
     * 订单的数据进入      /order/add_order
     * 前端传递必须参数：type
     *  购物车的传递购物车ID，数量    支付信息    地址ID
     *  单独购物的需要DKUID,数量，支付信息，地址ID
     *  前端传递非必须参数：
     *
     * 回调结果：200  拉取数据成功
     *
     *回调数据：  订单列表数据
     */
    public function add_order(Request $request,Compute $compute,Notify $notify){
        $user_info			=$request->get('user_info');
        $address_id			=$request->input('address_id');			//接受一个地址ID过来
        $price				=$request->input('price');
        $number				=$request->input('number');
        $now_time			=date('Y-m-d H:i:s',time());
        $good_id		    ='good_202010261916293512632330';
        //$address_id			='address_202011281348143128538349';
        $wallet_flag    =$request->input('wallet_flag');
        $use_money         =$user_info->userCapital['money'];
        $where_address=[
            ['self_id','=',$address_id],
            ['delete_flag','=','Y'],
        ];
        $address_info=DB::table('user_address')->where($where_address)
            ->select('self_id as address_id',
                'name',
                'tel',
                'address'
            ) ->first();
		if(empty($address_info)){
            $msg['code']=300;
            $msg['msg']='没有地址';

            //dd($pay_order_sn);
            return $msg;
		}

//DUMP($address_info);
        $good_where=[
            ['self_id','=',$good_id],
        ];
        $selectGood=['good_title','group_code','group_name','good_name','thum_image_url','good_type'];
        $good_info=DB::table('erp_shop_goods')->where($good_where)->select($selectGood)->first();

        $pay_order_sn=generate_id('O');

        $wallet_money=0;
        if($wallet_flag=='Y'){
            if($price*$number > $use_money ){
                // 5*100 = 500   300   $wallet_money=300

                //如果要支付的金额   》用户的余额
                $wallet_money=$use_money;
            }else{
                // 5*100 = 500   700   $wallet_money=500
                $wallet_money=$price*$number;
            }
        }


        $order['gather_address_id']=$address_info->address_id;
        $order['gather_name']=$address_info->name;// 收货人姓名
        $order['gather_tel']=$address_info->tel;
        $order['gather_address']=$address_info->address;
        $order['self_id']=generate_id('O');      			//商户订单号
        $order['total_user_id']=$user_info->total_user_id;
        $order['pay_order_sn']=$pay_order_sn;
        $order['pay_status']='1'; 							//订单状态，1未支付2已支付(待收货）3配送中,4已完成,5取消订单,6已退款,7已评价的订单,8已关闭,9已送达
        $order['logistics_status']='1';						//物流状态(1,待拣货，2拣货中，3待配送，4配送中，5已完成，6退货中，7已退货)
        $order['order_type']='share';					//团购group，单独购买alone，赠送give,积分integral，批发wholesale,购物车cart，扫码购scan，股份share
        $order['create_time']=$order['update_time']=$now_time;
        $order['group_code']=$good_info->group_code;
        $order['group_name']=$good_info->group_name;
        $order['show_group_code']=$good_info->group_code;			//那个门店发起的
        $order['show_group_name']=$good_info->group_name;			//那个门店发起的
        $order['money_goods']=$price*$number;				//商品总价值
        $order['money_serves']='0';				//服务总计
        $order['discounts_single_total']='0';	//单品总计
        $order['discounts_all_total']='0';		//全场总计
        $order['discounts_activity_total']='0';	//活动总计
        $order['pay_wallet_money']=$wallet_money;


        $id=DB::table('shop_order')->insert($order);

        $order_list['total_user_id']=$user_info->total_user_id;
        $order_list['self_id']=generate_id('list_');
        $order_list['order_sn']=$order['self_id'];
        $order_list['pay_status']='1'; 							//订单状态，1未支付2已支付(待收货）3配送中,4已完成,5取消订单,6已退款,7已评价的订单,8已关闭,9已送达
        $order_list['pay_order_sn']=$pay_order_sn;
        $order_list['price']=$price;//单价
        $order_list['number']=$number;//数量
        $order_list['good_id']=$good_id;//商品id
        $order_list['good_title']=$good_info->good_title;							//商品的标题
        $order_list['good_name']=$good_info->good_name;							//商品的名称
        $order_list['good_img']=$good_info->thum_image_url;						//商品的图片
        $order_list['good_type']=$good_info->good_type;							//商品类型，用于订单中解开good_info
        $order_list['group_code']=$good_info->group_code;
        $order_list['group_name']=$good_info->group_name;
        $order_list['create_time']=$order_list['update_time']=$now_time;



        DB::table('shop_order_list')->insert($order_list);

        //开始做支付信息板块
        $order_pay['self_id']=$pay_order_sn;
        $order_pay['total_user_id']=$user_info->total_user_id;
        $order_pay['token_id']=$user_info->token_id;
        $order_pay['pay_status']='1';//订单状态，1未支付2已支付(待收货）3配送中,4已完成,5取消订单,6已退款,7已评价的订单,8已关闭,9已送达
        $order_pay['create_time']=$order_pay['update_time']=$now_time;
        //$order_pay['ip']=$ip;
        $order_pay['pay_type']='order';
        $order_pay['pay_wallet_money']=$wallet_money;
        $order_pay['pay_money']=$price*$number - $wallet_money;


        $order_pay['show_group_code']='1234';			//那个门店发起的
        $order_pay['show_group_name']=$good_info->group_name;			//那个门店发起的
        //$order_pay['pay_info']=json_encode($pay_check);
        $order_pay['hosturl']='shop.yipailiangde.com';
        //dd($order_pay);

        DB::table('pay')->insert($order_pay);


        if($order_pay['pay_money']  > 0){
            $msg['code']=200;
            $msg['pay_order_sn']=$pay_order_sn;
            $msg['msg']='加入订单成功';
            //dd($pay_order_sn);
            return $msg;

        }else{
            $parameter['out_trade_no']=$pay_order_sn;
            $parameter['total_fee']=$order_pay['pay_money'];
            $parameter['mch_id']=null;
            $parameter['time_end']=date('YmdHis',time());//20201128190636;
            $parameter['transaction_id']=null;
            $now_time=date('Y-m-d H:i:s',time());
			$order_info=(object)$order_pay;
//DUMP($order_info);
		//object($order_pay);
            $notify->process($parameter,$order_info,$now_time,$compute);
            $msg['code']=201;
            //dd(1111);
            return $msg;


        }

//
//        $msg['code']=200;
//        $msg['pay_order_sn']=$pay_order_sn;
//        $msg['msg']='加入订单成功';
        //dd($pay_order_sn);
//        return $msg;



    }


    /**
     * 订单的分页数据      /order/order_page
     * 前端传递必须参数：page    status（订单的状态，如果是全部则传all）
     *前端传递非必须参数：
     *
     * 回调结果：200  拉取数据成功
     *
     *回调数据：  订单列表数据
     */
    public function order_page(Request $request){
        $user_info			=$request->get('user_info');
        $pay_status         =config('shop.pay_status');
		$status	    =$request->input('status')??'all';
		/** 虚拟数据
		$status=1;*/

		//先把这个状态下的达到的修改成为已读取
        //dump($user_info->toArray());
        $listrows=config('page.listrows')[1];//每次加载的数量
		$first=$request->input('page')??1;
		$firstrow=($first-1)*$listrows;


			if($status == "all"){
				$order_where=[
					['total_user_id','=',$user_info->total_user_id],
					['delete_flag','=','Y'],
				];
			}else{
				$order_where=[
					['total_user_id','=',$user_info->total_user_id],
					['pay_status','=',$status],
					['delete_flag','=','Y'],
				];
			}

			$datt['read_flag']='Y';
			$datt['update_time']=date('Y-m-d H:i:s',time());

        	ShopOrder::where($order_where)->update($datt);

            $select=['self_id','pay_order_sn','pay_status','order_type','money_goods','create_time','group_name','show_group_name','gather_name','gather_tel','gather_address'];

            $shopOrderListSelect=['order_sn','good_title','good_img','price','number','real_nubmer'];
            $data['items']=ShopOrder::with(['shopOrderList' => function($query)use($shopOrderListSelect) {
                $query->select($shopOrderListSelect);
            }])->where($order_where)
                ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                ->select($select)->get();

            foreach ($data['items'] as $k => $v){
                $v->pay_status_color=$pay_status[$v->pay_status-1]['pay_status_color'];
                $v->pay_status_text=$pay_status[$v->pay_status-1]['pay_status_text'];

                foreach ($v->shopOrderList as $kk => $vv){
                    $vv->good_img=img_for($vv->good_img,'one');
                    $vv->price=number_format($vv->price/100, 2);
                }
            }


			$msg['code']=200;
			$msg['msg']='数据拉取成功！';
			$msg['data']=$data;
//dd($msg);
        return $msg;
    }

    /**
     * 订单的分页数据      /order/order_detail
     * 前端传递必须参数：order_sn
     *前端传递非必须参数：
     *
     * 回调结果：200  拉取数据成功
     *
     *回调数据：  订单详情
     */

    public function order_detail(Request $request){
        $pay_status	=config('shop.pay_status');
		$order_sn	=$request->input('order_sn');
        $now_time	=date('Y-m-d H:i:s',time());
        //$order_sn	='O202010231845212137514382';

        $order_where=[
            ['self_id','=',$order_sn],
        ];

        $select=['self_id','pay_order_sn','pay_status','order_type','money_goods','create_time','group_name','show_group_name','gather_name','gather_tel','gather_address','pay_wallet_money'];

        $shopOrderListSelect=['order_sn','good_title','good_img','price','number','real_nubmer'];
        $data['items']=ShopOrder::with(['shopOrderList' => function($query)use($shopOrderListSelect) {
            $query->select($shopOrderListSelect);
        }])->where($order_where)
            ->select($select)->first();

		if($data['items']){
		    //处理一下订单的信息
            $data['items']->money_goods=number_format($data['items']->money_goods/100, 2);
			$data['items']->pay_wallet_money=number_format($data['items']->pay_wallet_money/100, 2);
            $data['items']->pay_status_color=$pay_status[$data['items']->pay_status-1]['pay_status_color'];
            $data['items']->pay_status_text=$pay_status[$data['items']->pay_status-1]['pay_status_text'];


            foreach ($data['items']->shopOrderList as $k => $v){
                $v->good_img=img_for($v->good_img,'one');
                $v->price=number_format($v->price/100, 2);
            }

			/***查询下物流信息出来**/
            $carriage_where=[
                ['order_sn','=',$order_sn],
                ['delete_flag','=','Y'],
            ];

            $data['carriage']=ShopOrderCarriage::where($carriage_where)->select('self_id','deliver_company','deliver_company_sn')
				->orderBy('create_time','desc')->get();


			$msg['code']=200;
			$msg['msg']='数据拉取成功！';
			$msg['data']=$data;
			$msg['complain_show']='N';
            return $msg;
		}else{
			$msg['code']=300;
			$msg['msg']='查询不到订单信息';
            $msg['complain_show']='N';
            return $msg;
		}

    }



    /**
     * 订单的确认收货    /order/order_receipt
     * 前端传递必须参数：order_sn
     *前端传递非必须参数：
     *
     * 回调结果：200  拉取数据成功
     *
     *回调数据：  订单详情
     */

    public function order_receipt(Request $request){
        $user_info		=$request->get('user_info');
        $order_sn		=$request->get('order_sn');
        $now_time		=date('Y-m-d H:i:s',time());

        $order_sn='O202010231845212137514382';



		 $order_where=[
			 ['self_id','=',$order_sn],
			 ['total_user_id','=',$user_info->total_user_id],
		 ];

		 $order_info=ShopOrder::where($order_where)
			 ->select('self_id as order_sn',
				 'pay_status'
			 )
			 ->first();
//dump($order_info);
             if($order_info){
                if( $order_info->pay_status == 3){

                    $data['pay_status']		=9;
                    $data['update_time']	=$now_time;

                    $id=ShopOrder::where($order_where)->update($data);


                    if($id){
						$order_detail_info['pay_status']='9';
						$order_detail_info['pay_status_color']='#0A0A0A';
						$order_detail_info['pay_status_text']='已送达';
                        $msg['code']=200;
                        $msg['msg']='确认收货成功！';
						$msg['data']=(object)$order_detail_info;

                    }else{
                        $msg['code']=301;
                        $msg['msg']='确认收货失败！';
                    }
                }else{
                    $msg['code']=302;
                    $msg['msg']='订单号状态无法更改！';
                }
             }else{
                 $msg['code']=300;
                 $msg['msg']='查询不到订单号！';
             }

        //dd($msg);
        return $msg;
    }

    /**
     * 订单的修改价格及核销拉取数据     /order/get_order_detail
     * 前端传递必须参数：order_sn
     *前端传递非必须参数：
     *
     * 回调结果：200  拉取数据成功
     *
     *回调数据：  订单详情
     */

    public function get_order_detail(Request $request){
        $user_info		=$request->get('user_info');
        $order_sn		=$request->input('order_sn');
        $now_time		=date('Y-m-d H:i:s',time());

        //$order_sn='O202010231845212137514382';
		//首先要判断这个用户是不是有修改价格的权限
				//dd($request->all());

		//$order_sn='O202006222223267354985581';
		//$user_id='user_202006222209180753589327';


			//通过订单号拿到show_group_code   关联  到  system_admin   和   user_id  去查询数据     如果有数据，则说明可以修改价格，如果拿不到，是空的，说明没有权限修改价格
			$order_where=[
				['a.self_id','=',$order_sn],
				['b.total_user_id','=',$user_info->total_user_id],
			];

			$order_info=DB::table('shop_order as a')
                ->join('system_admin as b',function($join){
                    $join->on('a.show_group_code','=','b.group_code');
                }, null,null,'left')
                ->where($order_where)
			    ->select(
					'a.self_id as order_sn',
					'a.pay_status',
					'a.show_group_code',
					'a.money_goods',
					'a.pay_to_money',
					'a.create_time'
				)
				->first();




			if($order_info ){
				if($order_info->pay_status == '1' || $order_info->pay_status == '9'){
					//拿到商品的详情
					$order_where2=[
						['order_sn','=',$order_sn],
					];

					$order_info->goods_info=DB::table('shop_order_list')
						->where($order_where2)
						->select(
							'price',
							'number',
							'good_title',
							'good_img'
						)
						->get()->toArray();

					//处理下金额和图片
					$order_info->money_goods=number_format($order_info->money_goods/100, 2);
					$order_info->pay_to_money=number_format($order_info->pay_to_money/100, 2);

					foreach($order_info->goods_info as $k => $v){
                        $v->good_img=img_for($v->good_img,'one');
						$v->price=number_format($v->price/100, 2);

					}
					$msg['code']=200;
					$msg['msg']='数据拉取成功！';
					$msg['data']=$order_info;
				}else{
					//该订单状态无法修改
					$msg['code']=302;
					$msg['msg']='该订单状态无法修改！';
				}


			}else{
				//没有权限核销
				$msg['code']=301;
				$msg['msg']='您没有权限处理该订单！';
			}


        //dd($msg);
        return $msg;
    }




    /**
     * 修改价格的操作进入数据库     /order/order_changge_price_do
     * 前端传递必须参数：order_sn
     *前端传递非必须参数：
     *
     * 回调结果：200  拉取数据成功
     *
     *回调数据：  订单详情
     */

    public function order_changge_price_do(Request $request){
        $user_info=$request->get('user_info');
		$orderData=$request->input('orderData');
		$order_sn=$orderData['order_sn'];
        $now_time=date('Y-m-d H:i:s',time());
		//$order_sn='O202005041031173479716917';
		//$user_id='user_202005031459423287249233';

			//通过订单号拿到show_group_code   关联  到  system_admin   和   user_id  去查询数据     如果有数据，则说明可以修改价格，如果拿不到，是空的，说明没有权限修改价格
			$order_where=[
				['a.self_id','=',$order_sn],
				['b.user_id','=',$user_info->user_id],
			];
			//dd($order_where);
			$order_info=DB::table('shop_order_pay as a')
                ->join('system_admin as b',function($join){
                    $join->on('a.show_group_code','=','b.group_code');
                }, null,null,'left')
                ->where($order_where)
			    ->select(
					'a.user_id',
					'a.self_id as order_sn',
					'a.pay_status',
					'a.show_group_code',
					'a.good_money',
					'a.pay_to_money',
					'a.offer_total',
					'a.create_time'
				)
				->first();
			if($order_info ){
				if($order_info->pay_status == '1'){
					//这里执行业务逻辑，只要把支付订单中的  pay_to_money   金额修改掉就可以了，其他业务逻辑是不是需要触发？？？
					$money=$orderData['money'];
					//$money='100';
					$werhir['self_id']=$order_sn;			//条件

					$data['pay_to_money']=intval(round($money*100));				//要取整数
					$data['update_time']=$now_time;					//要取整数
					//dd($data);
                    $id=DB::table('shop_order_pay')->where($werhir)->update($data);


					//写入OA
					$data_oa['self_id']=generate_id('oa_');
					$data_oa['user_id']=$order_info->user_id;
					$data_oa['pay_order_sn']=$order_sn;
					$data_oa['operation_type']='2';
					$data_oa['operation_total']='修改支付金额';
					$data_oa['old_info']=$order_info->pay_to_money;
					$data_oa['new_info']=$data['pay_to_money'];
					$data_oa['operating_way']='front';
					$data_oa['operating_time']=$now_time;
					$data_oa['create_time']=$data['update_time']=$now_time;
					//dd($data);
					DB::table('shop_order_oa')->insert($data_oa);


					if($id){
						$msg['code']=200;
						$msg['msg']='支付总价修改成功！';
						$msg['data']=number_format($data['pay_to_money'] /100, 2);
					}else{
						$msg['code']=300;
						$msg['msg']='支付总价修改失败！';
					}


				}else{
					//该订单状态无法修改
					$msg['code']=302;
					$msg['msg']='该订单的状态无法修改价格！';
				}


			}else{
				//没有权限核销
				$msg['code']=301;
				$msg['msg']='您没有权限处理该订单！';
			}


        //dd($msg);
        return $msg;


    }




    /**
     * 订单的订单核销    /order/order_verification
     * 前端传递必须参数：order_sn
     *前端传递非必须参数：
     *
     * 回调结果：200  拉取数据成功
     *
     *回调数据：  订单详情
     */

    public function order_verification(Request $request){
		//dd($request->all());
        $user_info=$request->get('user_info');
		$order_sn=$request->input('order_sn');
        $now_time=date('Y-m-d H:i:s',time());
		//$order_sn='O202005041031173479716917';
		//$user_id='user_202005031459423287249233';

			//通过订单号拿到show_group_code   关联  到  system_admin   和   user_id  去查询数据     如果有数据，则说明可以修改价格，如果拿不到，是空的，说明没有权限修改价格
			$order_where=[
				['a.self_id','=',$order_sn],
				['b.user_id','=',$user_info->user_id],
			];
			//dd($order_where);
			$order_info=DB::table('shop_order_pay as a')
                ->join('system_admin as b',function($join){
                    $join->on('a.show_group_code','=','b.group_code');
                }, null,null,'left')
                ->where($order_where)
			    ->select(
					'a.user_id',
					'a.self_id as order_sn',
					'a.pay_status',
					'a.show_group_code',
					'a.good_money',
					'a.pay_to_money',
					'a.offer_total',
					'a.create_time'
				)
				->first();
				//dd($order_info);
			if($order_info){
				if($order_info->pay_status == '2'){
					//这里执行业务逻辑，只要把支付订单中的  pay_to_money   金额修改掉就可以了，其他业务逻辑是不是需要触发？？？
					//$money=$orderData['money'];
					//$money='100';
					$werhir['self_id']=$order_sn;			//条件
					$data['pay_status']=4;				//要取整数
					$data['update_time']=$now_time;					//要取整数
					//dd($data);
                    $id=DB::table('shop_order_pay')->where($werhir)->update($data);

					$werhir2222['pay_order_sn']=$order_sn;			//条件
					DB::table('shop_order')->where($werhir2222)->update($data);

					//写入OA
					$data_oa['self_id']=generate_id('oa_');
					$data_oa['user_id']=$order_info->user_id;
					$data_oa['pay_order_sn']=$order_sn;
					$data_oa['operation_type']='11';
					$data_oa['operation_total']='核销订单';
					$data_oa['old_info']='2';
					$data_oa['new_info']='4';
					$data_oa['operating_way']='front';
					$data_oa['operating_time']=$now_time;
					$data_oa['create_time']=$data['update_time']=$now_time;
					//dd($data);
					DB::table('shop_order_oa')->insert($data_oa);
					if($id){
						$msg['code']=200;
						$msg['msg']='核销成功！';
						$msg['data']=$order_info;
					}else{
						$msg['code']=300;
						$msg['msg']='核销失败！';
						$msg['data']=$order_info;
					}
				}else{
					//该订单状态无法修改
					$msg['code']=302;
					$msg['msg']='该订单的状态无法核销！';
				}
			}else{
				//没有权限核销
				$msg['code']=301;
				$msg['msg']='您没有权限处理该订单！';
			}

        //dd($msg);
        return $msg;
    }


    /**
     * 订单的投诉    /order/order_complain
     * 前端传递必须参数：order_sn
     *前端传递非必须参数：
     *
     * 回调结果：200  拉取数据成功
     *
     *回调数据：  订单详情
     */

    public function order_complain(Request $request){
        $user_info=$request->get('user_info');
		$Data=$request->get('Data');
		$order_sn=$Data['order_sn'];
		//dd($order_sn);

			$nowtime=date('Y-m-d H:i:s',time());

			$data_oa['self_id']=generate_id('oa_');
            $data_oa['user_id']=$user_info->user_id;
            $data_oa['pay_order_sn']=$order_sn;
            $data_oa['operation_type']='9';
            $data_oa['operation_total']='投诉';
            $data_oa['operating_way']='front';
            $data_oa['operating_time']=$nowtime;
			$data_oa['name']=$Data['complaint_name'];
			$data_oa['tel']=$Data['complaint_tel'];
			$data_oa['complain']=$Data['complaint_info'];
            $data_oa['create_time']=$data['update_time']=$nowtime;
            //dd($data_oa);
            $id=DB::table('shop_order_oa')->insert($data_oa);


			if($id){
				$msg['code']=200;
				$msg['msg']='投诉成功！';
				$msg['data']=$data;
			}else{
				$msg['code']=300;
				$msg['msg']='投诉失败！';
				$msg['data']=$data;
			}

		//dd($msg);

        return $msg;
    }


    /**
     * 订单的售后服务    /order/order_service
     * 前端传递必须参数：order_sn
     *前端传递非必须参数：
     *
     * 回调结果：200  拉取数据成功
     *
     *回调数据：  订单详情
     */

    public function order_service(Request $request){

        $order_sn='BY202001310810317472320663';

        $order_where=[
            ['self_id','=',$order_sn],
        ];

        $order_detail_info=DB::table('shop_order')->where($order_where)
            ->first();

        $msg['code']=200;
        $msg['msg']='数据拉取成功！';
        $msg['data']=$order_detail_info;

        return $msg;
    }

    //删除订单
    public function order_delete(Request $request){
        $user_info=$request->get('user_info');
        $order_sn=$request->input('order_sn');
        $now_time=date('Y-m-d H:i:s',time());
	//dd($user_info);
        $order_where=[
                 ['self_id','=',$order_sn],
                 ['total_user_id','=',$user_info->total_user_id],
        ];
	//dd($order_where);
        $data['delete_flag']='N';
        $data['update_time']=$now_time;
        $id=DB::table('shop_order')->where($order_where)->update($data);

         if($id){
                        $msg['code']=200;
                        $msg['msg']='删除订单成功！';
                    }else{
                        $msg['code']=303;
                        $msg['msg']='删除订单失败！';
                    }

        return $msg;
    }





	    /**
     * 订单的取消订单    /order/order_cancel
     * 前端传递必须参数：order_sn
     *前端传递非必须参数：
     *
     * 回调结果：200  拉取数据成功
     *
     *回调数据：  订单详情
     */

    public function order_cancel(Request $request){

        $user_info=$request->get('user_info');
        $order_sn=$request->input('order_sn');
        $now_time=date('Y-m-d H:i:s',time());

        //$user_id='user_202004021006499587468765';
        //$order_sn='O202004191654031267296628';

             $order_where=[
                 ['self_id','=',$order_sn],
                 ['user_id','=',$user_info->user_id],
             ];

             $order_info=DB::table('shop_order_pay')->where($order_where)
                 ->select('self_id as order_sn',
                     'pay_status'
                 )
                 ->first();

             if($order_info){
                if( $order_info->pay_status == 1){

                    $shop_order_pay['self_id']=$order_sn;
                    $shop_order['pay_order_sn']=$order_sn;


                    $data['pay_status']=5;
                    $data['update_time']=$now_time;
                    $id=DB::table('shop_order_pay')->where($shop_order_pay)->update($data);

                    DB::table('shop_order')->where($shop_order)->update($data);

					//写入OA
					$data_oa['self_id']=generate_id('oa_');
					$data_oa['user_id']=$user_info->user_id;
					$data_oa['pay_order_sn']=$order_sn;
					$data_oa['operation_type']='10';
					$data_oa['operation_total']='取消订单';
					$data_oa['old_info']='1';
					$data_oa['new_info']='5';
					$data_oa['operating_way']='front';
					$data_oa['operating_time']=$now_time;
					$data_oa['create_time']=$data['update_time']=$now_time;
					//dd($data);
					DB::table('shop_order_oa')->insert($data_oa);


                    if($id){
						$order_detail_info['pay_status']='5';
						$order_detail_info['pay_status_color']='#FF0000';
						$order_detail_info['pay_status_text']='已取消';
                        $msg['code']=200;
                        $msg['msg']='取消订单成功！';
						$msg['data']=(object)$order_detail_info;
                    }else{
                        $msg['code']=303;
                        $msg['msg']='取消订单失败！';
                    }
                }else{
                    $msg['code']=302;
                    $msg['msg']='订单状态无法更改！';
                }
             }else{
                 $msg['code']=301;
                 $msg['msg']='查询不到订单号！';
             }

        //dd($msg);
        return $msg;


    }

	public function add_order_do($good_infos,$pay_check,$group_code,$user_info,$address_info,$cart_type,$show_group_name,$ip,$hosturl){
        /**
        $pay['goods_total_money']='0';						//商品总金额
        $pay['discounts_single_total']='0';                 //单品券优惠总计
        $pay['discounts_all_total']='0';                    //全场券优惠总计
        $pay['discounts_activity_total']='0';               //活动优惠总计
        $pay['discounts_total_money']='0';                  //总计优惠
        $pay['serve_total_money']='0';                      //服务总费用
        $pay['kehu_yinfu']='0';
        $pay['good_count']='0';                         //商品总数量*/
        /**活动优惠计算开始 */

        //第一步，根据商品的group_code进行订单拆分
        $group_code=array_unique($group_code);
        //dd($group_code);
        foreach($group_code as $k => $v){
            foreach($good_infos as $kk => $vv){
                if($v==$vv->group_code){
                    $order_info[$k]['info'][]=$vv;

                }
            }
        }

        //dd($good_infos);
        //去抓取平台方的公司进来
        $show_group_where['group_code']=$show_group_name;

        $show_group_info=DB::table('system_group')->where($show_group_where)->select('group_code','group_name')->first();

        //dd($show_group_info);
        //dd($address_info);

        //做一个支付单号过来，就是客户显示的订单号
        $pay_order_sn=generate_id('O');

        $buy_time=date("Y-m-d : H-i-s",time());

        /***开始做订单数据
        if($address_info->address_id =='001'){
            //dump(1111);
            $order_pay['gather_address_id']=$address_info->address_id;
            $order_pay['gather_name']=$address_info->name;// 收货人姓名
        }else{
            //dump(2222);

            $order_pay['gather_name']=$address_info->name;									// 收货人姓名
            $order_pay['gather_tel']=$address_info->tel;									// 收货人电话
            $order_pay['gather_address']=$address_info->address.$address_info->particular;	//收货地址
            $order_pay['gather_address_id']=$address_info->address_id;
            $order_pay['gather_sheng']=$address_info->sheng_name;
            $order_pay['gather_shi']=$address_info->shi_name;
            $order_pay['gather_qu']=$address_info->qu_name;
            $order_pay['gather_address_longitude']=$address_info->longitude;				//用户地址经度
            $order_pay['gather_address_latitude']=$address_info->dimensionality;			//用户地址纬度
        }

		**/
        //做一个新的数组，把这个里面使用到的优惠券都拉进来，然后准备对他进行处理工作
        $used_coupon=[];
        foreach($order_info as $k => $v){
            if($address_info->address_id =='001'){
                //dump(1111);
                $order['gather_address_id']=$address_info->address_id;
                $order['gather_name']=$address_info->name;// 收货人姓名
            }else{
                //dump(2222);

                $order['gather_name']=$address_info->name;									// 收货人姓名
                $order['gather_tel']=$address_info->tel;									// 收货人电话
                $order['gather_address']=$address_info->address.$address_info->particular;	//收货地址
                $order['gather_address_id']=$address_info->address_id;
                $order['gather_sheng']=$address_info->sheng_name;
                $order['gather_shi']=$address_info->shi_name;
                $order['gather_qu']=$address_info->qu_name;
                $order['gather_address_longitude']=$address_info->longitude;				//用户地址经度
                $order['gather_address_latitude']=$address_info->dimensionality;			//用户地址纬度
            }


            //dd($v);
            $order['self_id']=generate_id('O');      			//商户订单号
            $order['total_user_id']=$user_info->total_user_id;
            $order['pay_order_sn']=$pay_order_sn;
            $order['pay_status']='1'; 							//订单状态，1未支付2已支付(待收货）3配送中,4已完成,5取消订单,6已退款,7已评价的订单,8已关闭,9已送达
            $order['logistics_status']='1';						//物流状态(1,待拣货，2拣货中，3待配送，4配送中，5已完成，6退货中，7已退货)
            $order['order_type']=$cart_type;					//团购group，单独购买alone，赠送give,积分integral，批发wholesale,购物车cart，扫码购scan
            $order['create_time']=$order['update_time']=$buy_time;
            $order['group_code']=$v['info'][0]->group_code;
            $order['group_name']=$v['info'][0]->group_name;
            $order['show_group_code']=$show_group_info->group_code;			//那个门店发起的
            $order['show_group_name']=$show_group_info->group_name;			//那个门店发起的

            //$order['shop_show_group_name']='0';



            /**   做支付中的地方*/
            //初始化费用
            $order['money_goods']='0';				//商品总价值
            $order['money_serves']='0';				//服务总计
            $order['discounts_single_total']='0';	//单品总计
            $order['discounts_all_total']='0';		//全场总计
            $order['discounts_activity_total']='0';	//活动总计
            //dd($pay);

            //循环写入小订单
            foreach($v['info'] as $kk => $vv){
                //dump($vv);
                $order['money_goods']+=$vv->sale_price*$vv->good_number; 		//商品总金额
                $order['discounts_single_total']+=$vv->discounts_single; 		//商品总金额
                $order['discounts_all_total']+=$vv->discounts_all_total; 		//商品总金额

                $order_list['total_user_id']=$user_info->total_user_id;
                $order_list['self_id']=generate_id('list_');
                $order_list['order_sn']=$order['self_id'];
                $order_list['pay_order_sn']=$pay_order_sn;
                $order_list['price']=$vv->sale_price;//单价
                $order_list['number']=$vv->good_number;//数量
                $order_list['good_id']=$vv->good_id;//商品id
                $order_list['good_sku_id']=$vv->sku_id;//good_sku_id
                $order_list['classify_id']=$vv->classify_id;//分类ID
                $order_list['classify_name']=$vv->classify_name;//分类名称
                $order_list['parent_classify_id']=$vv->parent_classify_id;//大分类ID
                $order_list['parent_classify_name']=$vv->parent_classify_name;//大分类名称
                $order_list['good_title']=$vv->good_title;							//商品的标题
                $order_list['good_name']=$vv->good_name;							//商品的名称
                $order_list['good_img']=$vv->thum_image_url;						//商品的图片
                $order_list['good_type']=$vv->good_type;							//商品类型，用于订单中解开good_info
                $order_list['good_info']=$vv->good_info;
                $order_list['discounts_all_total']=$vv->discounts_all_total;//全场券优惠的密等拆分
                $order_list['cart_id']=$vv->cart_id;
                $order_list['integral_scale']=$vv->integral_scale;
                $order_list['group_code']=$vv->group_code;
                $order_list['group_name']=$vv->group_name;
                $order_list['coupon_id']=$vv->coupon_id;//单品券的ID
                $order_list['discounts_single']=$vv->discounts_single;//单品券优惠
                $order_list['create_time']=$order_list['update_time']=$buy_time;
                if($vv->coupon_id){
                    $order_list['coupon_id']=$vv->coupon_id;
                    $used_coupon[]=$vv->coupon_id;							//如果有单品优惠，把单品优惠的东西拿过来处理一下
                }


                DB::table('shop_order_list')->insert($order_list);


                $wwee['self_id']=$vv->cart_id;
                $wwee['delete_flag']='Y';
                $wwee['use_flag']='Y';

                $cartdata['use_flag']='N';
                $cartdata['delete_time']=$buy_time;
                $cartdata['delete_cause']='写入订单';
                $cartdata['checked_state']='N';
                $cartdata['order_sn']=$order['self_id'];
                DB::table('user_cart')->where($wwee)->update($cartdata);


            }

            if($order['discounts_all_total'] > 0){
                $order['discounts_usercoupon_id']=$pay_check['discounts_usercoupon_id'];
                $used_coupon[]=$pay_check['discounts_usercoupon_id'];
            }


            DB::table('shop_order')->insert($order);
        }


        // dd($order);
        //开始做支付信息板块
        $order_pay['self_id']=$pay_order_sn;
        $order_pay['total_user_id']=$user_info->total_user_id;
        $order_pay['token_id']=$user_info->token_id;
        $order_pay['pay_status']='1';//订单状态，1未支付2已支付(待收货）3配送中,4已完成,5取消订单,6已退款,7已评价的订单,8已关闭,9已送达
        $order_pay['create_time']=$order_pay['update_time']=$buy_time;
        $order_pay['ip']=$ip;
        $order_pay['pay_type']='order';
        $order_pay['pay_money']=$pay_check['kehu_yinfu'];
        $order_pay['show_group_code']=$show_group_info->group_code;			//那个门店发起的
        $order_pay['show_group_name']=$show_group_info->group_name;			//那个门店发起的
        $order_pay['pay_info']=json_encode($pay_check);
        $order_pay['hosturl']=$hosturl;
        //dd($order_pay);
        DB::table('pay')->insert($order_pay);

        //要把优惠券做成一个锁定，防止优惠券被重复使用
        if($used_coupon){

            //对这个东西进行去重
            $used_coupon = array_unique($used_coupon);
            //把这里里面的优惠券进行锁定处理，等支付完成后，进行已使用处理

            $used_coupon_data['coupon_status']='lock';
            $used_coupon_data['update_time']=$buy_time;
            $used_coupon_data['user_order_sn']=$pay_order_sn;

            DB::table('user_coupon')->whereIn('self_id',$used_coupon)->update($used_coupon_data);

            //dd($used_coupon);

        }


        //dd($order_pay);


        //dd($good_infos);
        //dd($pay);
        //dd(1111);
        $msg['code']=200;
        $msg['pay_order_sn']=$pay_order_sn;
        return $msg;
    }




}
?>
