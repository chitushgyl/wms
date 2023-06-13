<?php
namespace App\Http\Admin\Wms;
use App\Models\Shop\ErpShopGoodsSku;
use App\Models\Wms\InoutOtherMoney;
use App\Models\Wms\WmsChangeGood;
use App\Models\Wms\WmsChangeList;
use App\Models\Wms\WmsLibrarySige;
use App\Models\Wms\WmsWarehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\FileController as File;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Wms\WmsGroup;
use App\Models\Group\SystemGroup;
use App\Http\Controllers\WmschangeController as Change;

class ChangeController extends CommonController{
    /***    业务公司列表      /wms/change/changeList
     */
    public function  changeList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $abc='业务公司';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/业务公司导入文件范本.xlsx',
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    //业务公司列表分页加载数据
    /***    业务公司分页      /wms/change/changePage
     */
    public function changePage(Request $request){
        /** 接收中间件参数**/
        $wms_cost_type_show    =array_column(config('wms.wms_cost_type'),'name','key');
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $company_name     =$request->input('company_name');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'company_name','value'=>$company_name],
        ];

        $where=get_list_where($search);
        $where1=[
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
        ];
        $select=[];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsChangeGood::where($where)->count(); //总的数据量
                $data['items']=WmsChangeGood::with(['WmsChangeList' => function($query)use($where1){
                    $query->where($where1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('create_time', 'desc')
//                    ->select($select)
                    ->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsChangeGood::where($where)->count(); //总的数据量
                $data['items']=WmsChangeGood::with(['WmsChangeList' => function($query)use($where1){
                    $query->where($where1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('create_time', 'desc')
//                    ->select($select)
                    ->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsChangeGood::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=WmsChangeGood::with(['WmsChangeList' => function($query)use($where1){
                    $query->where($where1);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('create_time', 'desc')
//                    ->select($select)
                    ->get();
                $data['group_show']='Y';
                break;
        }
//dump($wms_cost_type_show);

        foreach ($data['items'] as $k=>$v) {

            $v->button_info=$button_info;

        }

        //dump($data['items']->toArray());

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    业务公司创建      /wms/change/createChange
     */
    public function createChange(Request $request){

        /** 接收数据*/
        $self_id=$request->input('self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $where1=[
            ['delete_flag','=','Y'],
//            ['use_flag','=','Y'],
        ];
        $data['info']=WmsChangeGood::with(['WmsChangeList' => function($query)use($where1){
            $query->where($where1);
            $query->with(['InoutOtherMoney' => function($query)use($where1){
                $query->where($where1);
            }]);
        }])->where($where)->first();
        if($data['info']){

        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;


    }

    /***    业务公司添加进入数据库      /wms/change/addChange
     */
    public function addChange(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_group';

        $operationing->access_cause     ='创建/修改业务公司';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();
        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $group_code         =$request->input('group_code');
        $total_price        =$request->input('total_price');//总金额
        $total_num          =$request->input('total_num');//总件数
        $total_weight       =$request->input('total_weight');//总吨重
        $change_time        =$request->input('change_time');//调仓时间
        $remark             =$request->input('remark');//备注
        $company_name       =$request->input('company_name');//客户
        $company_id         =$request->input('company_id');//客户
        $more_money         =json_decode($request->input('more_money'),true);//其他费用
        $good_list          =json_decode($request->input('good_list'),true);


        $rules=[
            'group_code'=>'required',
            'company_name'=>'required',
        ];
        $message=[
            'group_code.required'=>'所属公司不能为空',
            'company_name.required'=>'公司名称不能为空',
        ];
        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            $contact_list = [];
            $address_list = [];
            $contact = [];
            $address_area = [];
            $deposit_id                         = generate_id('TC');
            $data['total_price']                = $total_price;
            $data['total_num']                  = $total_num;
            $data['total_weight']               = $total_weight;
            $data['change_time']                = $change_time;
            $data['company_name']               = $company_name;
            $data['company_id']           	    = $company_id;
            $data['remark']                 	= $remark;


            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;
            $deposit_list = [];
            $money_lists = [];
            DB::beginTransaction();
            try{
                foreach($good_list as $key => $value){
                    $where['self_id']=$value['sku_id'];
                    //查询商品是不是存在
                    $goods_select=['self_id','external_sku_id','company_id','company_name','good_name','good_english_name','wms_target_unit','wms_scale','wms_unit','wms_spec',
                        'wms_length','wms_wide','wms_high','wms_weight','period','period_value'];
                    //dump($goods_select);

                    $getGoods=ErpShopGoodsSku::where($where)->select($goods_select)->first();

                    if(empty($getGoods)){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行商品不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }
                    $list['sige_id']           =  $value['sige_id'];//原库存SELF_ID
                    $list['sku_id']            =  $value['sku_id'];//商品SELF_ID
                    $list['external_sku_id']   =  $value['external_sku_id'];//商品编号
                    $list['yuan_warehouse_id'] =  $value['yuan_warehouse_id'];//转出仓库self_id
                    $list['yuan_warehouse_name'] =  $value['yuan_warehouse_name'];//转出仓库名称
                    $list['warehouse_id']      =  $value['warehouse_id'];//转入仓库self_id
                    $list['warehouse_name']    =  $value['warehouse_name'];//转入仓库名称
                    $list['good_name']         =  $value['good_name'];//商品名称
                    $list['good_spac']         =  $value['good_spac'];//商品规格
                    $list['good_weight']       =  $value['good_weight'];//件重
//                $list['good_num']          =  $value['good_num'];//件数
                    $list['weight']            =  $value['weight'];//吨重
                    $list['num']               =  $value['num'];//数量
                    $list['plate_num']         =  $value['plate_num'];//板数
                    $list['plate_id']          =  $value['plate_id'];//板位
                    $list['inventory_num']     =  $value['inventory_num'];//库存件数
                    $list['inventory_count_num']  =  $value['inventory_count_num'];//库存计费数量
                    $list['remark']            =  $value['remark'];//备注
                    if ($self_id){
                        $list['change_id']         =  $self_id;//
                    }else{
                        $list['change_id']         =  $deposit_id;//
                    }

                    $wmsLibrarySige = WmsLibrarySige::where('self_id',$value['sige_id'])->first();
                    $library_sige['num']       = $wmsLibrarySige->num - $value['num'];
                    $library_sige['warehouse_id']       = $value['warehouse_id'];
                    $library_sige['warehouse_name']       = $value['warehouse_name'];

                    if($value['self_id']){
                        $list['update_time']  = $now_time;
                        WmsChangeList::where('self_id',$value['self_id'])->update($list);
                    }else{
                        $list["self_id"]            =generate_id('TG');
                        $list["group_code"]         =$getGoods->group_code;
                        $list["group_name"]         =$getGoods->group_name;
                        $list['create_time']        =$now_time;
                        $list["update_time"]        =$now_time;
                        $list['create_user_id']     = $user_info->admin_id;
                        $list['create_user_name']   = $user_info->name;
                        $deposit_list[] = $list;
                    }


                    foreach($value['more_money'] as $k => $v){
                        $money['price']             = $v['price'];
                        $money['money_id']          = $v['money_id'];
                        $money['number']            = $v['number'];
                        $money['total_price']       = $v['total_price'];
                        $money['bill_id']           = $v['bill_id'];
                        $money['use_flag']          = 'N';
                        $money['delete_flag']       = $v['delete_flag'];
                        if ($v['order_id'] == $value['self_id'] && $v['self_id']){
                            InoutOtherMoney::where('self_id',$v['self_id'])->update($money);
                        }else{
                            $money['self_id']           = generate_id('RF');
                            if($value['self_id']){
                                $money['order_id']          = $value["self_id"];
                            }else{
                                $money['order_id']          = $list["self_id"];
                            }

                            $money['group_code']        = $user_info->group_code;
                            $money['group_name']        = $user_info->group_name;
                            $money['create_user_id']    = $user_info->admin_id;
                            $money['create_user_name']  = $user_info->name;
                            $money['create_time']       = $money['update_time'] = $now_time;
                            $money_list[] = $money;
                            $money_lists = array_merge($money_list);
                        }

                    }

                    $library_sige_list[] = $library_sige;
//                $deposit_list[] = $list;
                    $a++;
                }


                $wheres['self_id'] = $self_id;
                $old_info=WmsChangeGood::where($wheres)->first();

                if($old_info){
                    $data['update_time'] = $now_time;
                    $id = WmsChangeGood::where('self_id',$self_id)->update($data);
                    WmsChangeList::insert($deposit_list);
                    InoutOtherMoney::insert($money_lists);

                    $operationing->access_cause='修改业务公司';
                    $operationing->operation_type='update';

                }else{

                    $data['self_id']=$deposit_id;		//优惠券表ID
                    $data['group_code'] = $group_code;
                    $data['group_name'] = SystemGroup::where('group_code','=',$group_code)->value('group_name');
                    $data['create_user_id']=$user_info->admin_id;
                    $data['create_user_name']=$user_info->name;
                    $data['create_time']=$data['update_time']=$now_time;
                    $id=WmsChangeGood::insert($data);

                    if ($id){
                        WmsChangeList::insert($deposit_list);
                    }

                    $operationing->access_cause='新建调仓表';
                    $operationing->operation_type='create';

                }

                $operationing->table_id=$old_info?$self_id:$data['self_id'];
                $operationing->old_info=$old_info;
                $operationing->new_info=$data;
                if($id){
                    DB::commit();
                    $msg['code'] = 200;
                    $msg['msg'] = "操作成功";
                    return $msg;
                }else{
                    DB::rollBack();
                    $msg['code'] = 302;
                    $msg['msg'] = "操作失败";
                    return $msg;
                }
            }catch (\Exception $e){
                dd($e);
                DB::rollBack();
                $msg['code'] = 302;
                $msg['msg'] = "操作失败";
                return $msg;
            }



        }else{
            //前端用户验证没有通过
            $erro=$validator->errors()->all();
            $msg['code']=300;
            $msg['msg']=null;
            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            return $msg;
        }

    }


    /**
     * 审核  /wms/change/updateChange
     * */
    public function updateChange(Request $request,Change $change){
$operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_library_order';

        $operationing->access_cause     ='修改入库状态';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $input              = $request->all();
        $self_id = $request->input('self_id');
        $group_code = $request->input('group_code');
        $order_status = $request->input('order_status');

        //第一步，验证数据
        $rules=[
            'self_id'=>'required',
//            'order_status'=>'required',
        ];
        $message=[
            'self_id.required'=>'请选择入库订单',
//            'order_status.required'=>'请选择要做的操作',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;
            $wms_change_good = WmsChangeGood::where('self_id',$self_id)->first();
            $wms_change_list = WmsChangeList::where('change_id',$self_id)->get();
            DB::beginTransaction();
            try{
                foreach ($wms_change_list as $key => $value){
                    //判断商品是否存在
                    $where['self_id']=$value['sku_id'];
                    $goods_select=['self_id','external_sku_id','company_id','company_name','good_name','good_english_name','wms_target_unit','wms_scale','wms_unit','wms_spec',
                        'wms_length','wms_wide','wms_high','wms_weight','period','period_value'];
                    $getGoods=ErpShopGoodsSku::where($where)->select($goods_select)->first();
                    if(empty($getGoods)){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行商品不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }

                    //检查原仓库库存是否满足
                    $old_library_sige = WmsLibrarySige::where('self_id',$value['sige_id'])->first();
                    if(empty($old_library_sige)){
                        if($abcd<$errorNum){
                            $a=$key+1;
                            $strs .= '数据中的第'.$a."行商品无库存，请检查".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }else{
                        if($value['num'] >$old_library_sige->now_num){
                            if($abcd<$errorNum){
                                $a=$key+1;
                                $strs .= '数据中的第'.$a."行商品数量不足".'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }
                    }

                    //查询转入库是否存在
                    $where_sign=[
                        ['delete_flag','=','Y'],
                        ['self_id','=',$value['warehouse_id']],
                    ];

                    $warehouse_info=WmsWarehouse::where($where_sign)->first();

                    if(empty($warehouse_info)){
                        if($abcd<$errorNum){
                            $a=$key+1;
                            $strs .= '数据中的第'.$a."行转入库不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }

                    //做库位数据
                    $change_out=$old_library_sige->toArray();
                    $change_out['create_user_id']     =$user_info->admin_id;
                    $change_out['create_user_name']   =$user_info->name;
                    $change_out['create_time']        =$now_time;
                    $change_out["update_time"]        =$now_time;
                    $change_out["now_num_new"]        =$old_library_sige->now_num - $value['num'];
                    $change_out['weight']             =$old_library_sige->weight - $value['weight'];//吨重
                    $change_out['plate_num']          =$old_library_sige->plate_num - $value['plate_num'];//板数
                    $change_out['inventory_num']      =$old_library_sige->inventory_num - $value['inventory_num'];//库存件数
                    $change_out['inventory_count_num']=$old_library_sige->inventory_count_num - $value['inventory_count_num'];//库存计费数量
                    $change_out["good_target_unit"]   =$old_library_sige->good_target_unit;
                    $change_out["good_scale"]         =$old_library_sige->good_scale;
                    $change_out["good_unit"]          =$old_library_sige->good_unit;
                    $change_out["good_lot"]           =$old_library_sige->good_lot;
                    $old_change[]=$change_out;

                    $change_in["self_id"]            =generate_id('RK');
                    $change_in["order_id"]           =$old_library_sige->order_id;
                    $change_in["sku_id"]             =$value['sku_id'];
                    $change_in["external_sku_id"]    =$value['external_sku_id'];
                    $change_in["company_id"]         =$wms_change_good->company_id;
                    $change_in["company_name"]       =$wms_change_good->company_name;
                    $change_in["good_name"]          =$value['good_name'];
                    $change_in["good_english_name"]  =$old_library_sige->good_english_name;
                    $change_in["good_target_unit"]   =$old_library_sige->wms_target_unit;
                    $change_in["good_scale"]         =$old_library_sige->wms_scale;
                    $change_in["good_unit"]          =$old_library_sige->wms_unit;
                    $change_in["wms_length"]         =$old_library_sige->wms_length;
                    $change_in["wms_wide"]           =$old_library_sige->wms_wide;
                    $change_in["wms_high"]           =$old_library_sige->wms_high;
                    $change_in["wms_weight"]         =$old_library_sige->wms_weight;
                    $change_in["good_info"]          =json_encode($getGoods,JSON_UNESCAPED_UNICODE);
                    $change_in["warehouse_id"]       =$value['warehouse_id'];
                    $change_in["warehouse_name"]     =$value['warehouse_name'];
                    $change_in["production_date"]    =$old_library_sige->production_date;
                    $change_in["expire_time"]        =$old_library_sige->expire_time;
                    $change_in['spec']               =$old_library_sige->spec;
                    $change_in['initial_num']        =$value['num'];
                    $change_in['now_num']            =$value['num'];
                    $change_in['storage_number']     =$value['num'];
                    $change_in["group_code"]         =$old_library_sige->group_code;
                    $change_in["group_name"]         =$old_library_sige->group_name;
                    $change_in['create_time']        =$now_time;
                    $change_in["update_time"]        =$now_time;
                    $change_in['create_user_id']     = $user_info->admin_id;
                    $change_in['create_user_name']   = $user_info->name;
                    $change_in["grounding_status"]   ='N';
                    $change_in["good_remark"]        =$value['remark'];
                    $change_in["good_lot"]           =$value['good_lot'];
                    $change_in["plate_number"]       =$value['plate_number'];
                    $change_in["singe_plate_number"] =$value['singe_plate_number'];
                    $change_in["singe_weight"]       =$value['good_weight'];
                    $change_in["count_number"]       =$value['good_weight']*$value['num']/1000;
                    $change_in['bulk']               = $getGoods->wms_length*$getGoods->wms_wide*$getGoods->wms_high*$value['now_num'];
                    $change_in['weight']             = $value['good_weight']*$value['now_num'];


                    $new_change_info[] = $change_in;

                }
//                dd($old_change,$new_change_info);

                $update['state']  = 'Y';
                $update['update_time'] = $now_time;
                $id = WmsChangeGood::where('self_id',$self_id)->update($update);
                if ($id){
                    foreach ($old_change as $k => $v){
                        $where=[
                            ['self_id','=',$v['self_id']],
                        ];
                        $data['now_num']            =$v['now_num_new'];
                        $data['update_time']        =$now_time;

                        WmsLibrarySige::where($where)->update($data);
                    }
                    WmsLibrarySige::insert($new_change_info);
                    $change->change($old_change,'moveout');
                    $change->change($new_change_info,'movein');
                    DB::commit();
                    $msg['code'] = 200;
                    $msg['msg'] = "操作成功！";
                    return $msg;
                }else{
                    DB::rollBack();
                    $msg['code'] = 302;
                    $msg['msg'] = "操作失败";
                    return $msg;
                }
            }catch(\Exception $e){
                dd($e);
                DB::rollBack();
                $msg['code'] = 302;
                $msg['msg'] = "操作失败";
                return $msg;
            }

        }else{
            //前端用户验证没有通过
            $erro=$validator->errors()->all();
            $msg['code']=300;
            $msg['msg']=null;
            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            return $msg;
        }
    }

    /***    业务公司启用禁用      /wms/change/changeUseFlag
     */
    public function changeUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_deposit';
        $medol_name='WmsDeposit';
        $self_id=$request->input('self_id');
        $flag='useFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='启用/禁用';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$status_info['old_info'];
        $operationing->new_info=$status_info['new_info'];
        $operationing->operation_type=$flag;

        $msg['code']=$status_info['code'];
        $msg['msg']=$status_info['msg'];
        $msg['data']=$status_info['new_info'];

        return $msg;
    }

    /***    业务公司删除      /wms/change/changeDelFlag
     */
    public function changeDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_change_good';
        $medol_name='WmsChangeGood';
        $self_id=$request->input('self_id');
        $flag='delFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$status_info['old_info'];
        $operationing->new_info=$status_info['new_info'];
        $operationing->operation_type=$flag;

        $msg['code']=$status_info['code'];
        $msg['msg']=$status_info['msg'];
        $msg['data']=$status_info['new_info'];

        return $msg;


    }
    /***    业务公司获取     /wms/group/getCompany
     */
    public function getCompany(Request $request){
        $group_code=$request->input('group_code');
        $where=[
            ['delete_flag','=','Y'],
            ['group_code','=',$group_code],
        ];

        $data['info']=WmsGroup::where($where)->get();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    /***    业务公司导入     /wms/group/import
     */
    public function import(Request $request){
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $table_name         ='wms_pack';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建业务公司';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $group_code         =$request->input('group_code');
        $file_id            =$request->input('file_id');
        /****虚拟数据
        $input['importurl']    =$importurl="uploads/2020-10-13/业务公司导入文件范本.xlsx";
        $input['group_code']   =$group_code='1234';
         ***/

        $rules = [
            'group_code' => 'required',
            'importurl' => 'required',
        ];
        $message = [
            'group_code.required' => '请选择公司',
            'importurl.required' => '请上传文件',
        ];
        $validator = Validator::make($input, $rules, $message);
        if ($validator->passes()) {
            /**发起二次效验，1效验文件是不是存在， 2效验文件中是不是有数据 3,本身数据是不是重复！！！* */
            if(!file_exists($importurl)){
                $msg['code'] = 301;
                $msg['msg'] = '文件不存在';
                return $msg;
            }
            $res = Excel::toArray((new Import),$importurl);

            $info_check=[];
            if(array_key_exists('0', $res)){
                $info_check=$res[0];
            }

            /**  定义一个数组，需要的数据和必须填写的项目
            键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */

            $shuzu=[
                '业务公司' =>['Y','N','255','company_name'],
                '联系人' =>['N','Y','50','contacts'],
                '联系电话' =>['N','Y','50','tel'],
                '公司地址' =>['N','Y','50','address'],
                '结算方式' =>['N','Y','50','pay_type'],
                '入库费' =>['N','Y','50','preentry_price'],
                '出库费' =>['N','Y','50','out_price'],
                '仓储费' =>['N','Y','50','storage_price'],
                '分拣费' =>['N','Y','50','total_price'],
            ];
            $ret=arr_check($shuzu,$info_check);

            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }
            $info_wait=$ret['new_array'];

            $where_check=[
                ['delete_flag','=','Y'],
                ['self_id','=',$group_code],
            ];
            $info = SystemGroup::where($where_check)->select('group_name','group_code')->first();

            //dump($group_info);
            if(empty($info)){
                $msg['code'] = 302;
                $msg['msg'] = '公司不存在';
                return $msg;
            }


            /** 二次效验结束**/

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;

            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                $where=[
                    ['delete_flag','=','Y'],
                    ['company_name','=',$v['company_name']],
                ];
                $company_info = WmsGroup::where($where)->value('company_name');

                if($company_info){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行业务公司已存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('company_');
                    $list['company_name']       = $v['company_name'];

                    if($v['preentry_price'] == 0){
                        $list['preentry_type']      		='no';
                        $list['preentry_price']           	=0;
                    }else{
                        $abc= explode('元/',$v['preentry_price']);
                        switch ($abc[1]){
                            case '托':
                                $list['preentry_type']      		='pull';
                                break;
                            case 'KG':
                                $list['preentry_type']      		='weight';
                                break;
                            case '立方':
                                $list['preentry_type']      		='bulk';
                                break;

                        }
                        $list['preentry_price']           	=$abc[0]*100;
                    }


                    if($v['out_price'] == 0){
                        $list['out_type']      		='no';
                        $list['out_price']           	=0;
                    }else{
                        $abc= explode('元/',$v['out_price']);
                        switch ($abc[1]){
                            case '托':
                                $list['out_type']      		='pull';
                                break;
                            case 'KG':
                                $list['out_type']      		='weight';
                                break;
                            case '立方':
                                $list['out_type']      		='bulk';
                                break;

                        }
                        $list['out_price']           	=$abc[0]*100;
                    }

                    if($v['storage_price'] == 0){
                        $list['storage_type']      		='no';
                        $list['storage_price']           	=0;
                    }else{
                        $abc= explode('元/',$v['storage_price']);
                        switch ($abc[1]){
                            case '托':
                                $list['storage_type']      		='pull';
                                break;
                            case 'KG':
                                $list['storage_type']      		='weight';
                                break;
                            case '立方':
                                $list['storage_type']      		='bulk';
                                break;

                        }
                        $list['storage_price']           	=$abc[0]*100;
                    }

                    if($v['total_price'] == 0){
                        $list['total_type']      		='no';
                        $list['total_price']           	=0;
                    }else{
                        if(strpos($v['total_price'],'元/')!== false){
                            $abc= explode('元/',$v['total_price']);
                            //dd($abc);
                            switch ($abc[1]){
                                case '托':
                                    $list['total_type']      		='pull';
                                    break;
                                case 'KG':
                                    $list['total_type']      		='weight';
                                    break;
                                case '立方':
                                    $list['total_type']      		='bulk';
                                    break;

                            }
                            $list['total_price']           	=$abc[0]*100;

                        }else{
                            $list['total_type']      		='no';
                            $list['total_price']           	=0;
                        }


                    }

                    $list['group_code']         = $info->group_code;
                    $list['group_name']         = $info->group_name;
                    $list['pay_type']           = $v['pay_type'];
                    $list['create_user_id']     =$user_info->admin_id;
                    $list['create_user_name']   =$user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
                    $list['file_id']            =$file_id;
                    $datalist[]=$list;
                }


                $a++;
            }


            $operationing->new_info=$datalist;
            if($cando == 'N'){
                $msg['code'] = 305;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);

            //dd($datalist);
            $id= WmsGroup::insert($datalist);

            if($id){
                $msg['code']=200;
                /** 告诉用户，你一共导入了多少条数据，其中比如插入了多少条，修改了多少条！！！*/
                $msg['msg']='操作成功，您一共导入'.$count.'条数据';

                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']='操作失败';
                return $msg;
            }


        }else{
            $erro = $validator->errors()->all();
            $msg['msg'] = null;
            foreach ($erro as $k => $v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code'] = 300;
            return $msg;
        }

    }

    /***    业务公司导出     /wms/group/execl
     */
    public function execl(Request $request,File $file){
        $wms_cost_type_show    =array_column(config('wms.wms_cost_type'),'name','key');
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
        $input      =$request->all();
        /** 接收数据*/
        $group_code     =$request->input('group_code');
        //$group_code  =$input['group_code']   ='group_202011201701272916308975';
        //dd($group_code);
        $rules=[
            'group_code'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择公司',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){
            /** 下面开始执行导出逻辑**/
            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            //查询条件
            $search=[
                ['type'=>'=','name'=>'group_code','value'=>$group_code],
                ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ];
            $where=get_list_where($search);
            $select=['self_id','company_name','use_flag','group_name','contacts','address','tel',
                'preentry_type','preentry_price','out_type','out_price','storage_type','storage_price','total_type','total_price','pay_type'];
            $info=WmsGroup::where($where)->orderBy('create_time', 'desc')->select($select)->get();

            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "group_name"=>'所属公司',
                    "company_name"=>'业务往来公司',
                    "contacts"=>'联系人',
                    "tel"=>'联系电话',
                    "address"=>'公司地址',
                    "preentry_price"=>'入库费用',
                    "out_price"=>'出库费用',
                    "storage_price"=>'仓储费用',
                    "total_price"=>'分拣费用',
                    "use_flag"=>'状态',
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($info as $k=>$v){
                    $list=[];

                    $list['id']=($k+1);
                    $list['company_name']=$v->company_name;
                    $list['group_name']=$v->group_name;
                    $list['contacts']=$v->contacts;
                    $list['tel']=$v->tel;
                    $list['address']=$v->address;

                    if(array_key_exists($v->preentry_type, $wms_cost_type_show)){
                        $list['preentry_price']=number_format($v->preentry_price/100, 2).'元/'.$wms_cost_type_show[$v->preentry_type];
                    }else{
                        $list['preentry_price']='未设置分拣收费';
                    }
                    if(array_key_exists($v->out_type, $wms_cost_type_show)){
                        $list['out_price']=number_format($v->out_price/100, 2).'元/'.$wms_cost_type_show[$v->out_type];
                    }else{
                        $list['out_price']='未设置出库收费';
                    }
                    if(array_key_exists($v->preentry_type, $wms_cost_type_show)){
                        $list['storage_price']=number_format($v->storage_price/100, 2).'元/'.$wms_cost_type_show[$v->preentry_type];
                    }else{
                        $list['storage_price']='未设置仓储收费';
                    }
                    if(array_key_exists($v->total_type, $wms_cost_type_show)){
                        $list['total_price']=number_format($v->total_price/100, 2).'元/'.$wms_cost_type_show[$v->total_type];
                    }else{
                        $list['total_price']='未设置分拣收费';
                    }

                    if($v->use_flag == 'Y'){
                        $list['use_flag']='使用中';
                    }else{
                        $list['use_flag']='禁止使用';
                    }

                    $data_execl[]=$list;

                }
                /** 调用EXECL导出公用方法，将数据抛出来***/
                $browse_type=$request->path();
                $msg=$file->export($data_execl,$row,$group_code,$group_name,$browse_type,$user_info,$where,$now_time);
                return $msg;

            }else{
                $msg['code']=301;
                $msg['msg']="没有数据可以导出";
                return $msg;
            }
        }else{
            $erro=$validator->errors()->all();
            $msg['msg']=null;
            foreach ($erro as $k=>$v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code']=300;
            return $msg;
        }

    }

    /***    业务公司详情     /wms/group/details
     */
    public function  details(Request $request,Details $details){
        $wms_cost_type_show    =array_column(config('wms.wms_cost_type'),'name','key');
        $self_id=$request->input('self_id');
        $table_name='wms_group';
        $select=['self_id','group_code','group_name','use_flag','create_user_name','create_time',
            'company_name','contacts','address','tel',
            'preentry_type','preentry_price','out_type','out_price','storage_type','storage_price','total_type','total_price','pay_type'];
        //$self_id='group_202009282038310201863384';
        $info=$details->details($self_id,$table_name,$select);

        if($info){

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            if(array_key_exists($info->preentry_type, $wms_cost_type_show)){
                $info->preentry_type_show=$wms_cost_type_show[$info->preentry_type]??null;
            }else{
                $info->preentry_type_show='未设置入库收费';
            }
            if(array_key_exists($info->out_type, $wms_cost_type_show)){
                $info->out_type_show=$wms_cost_type_show[$info->out_type]??null;
            }else{
                $info->out_type_show='未设置出库收费';
            }

            if(array_key_exists($info->storage_type, $wms_cost_type_show)){
                $info->storage_type_show=$wms_cost_type_show[$info->storage_type]??null;
            }else{
                $info->storage_type_show='未设置仓储收费';
            }

            if(array_key_exists($info->total_type, $wms_cost_type_show)){
                $info->total_type_show=$wms_cost_type_show[$info->total_type]??null;
            }else{
                $info->total_type_show='未设置分拣收费';
            }

            $info->preentry_price = number_format($info->preentry_price/100, 2);
            $info->out_price = number_format($info->out_price/100, 2);
            $info->storage_price = number_format($info->storage_price/100, 2);
            $info->total_price = number_format($info->total_price/100, 2);

            $data['info']=$info;
            $log_flag='Y';
            $data['log_flag']=$log_flag;
            $log_num='10';
            $data['log_num']=$log_num;
            $data['log_data']=null;

            if($log_flag =='Y'){
                $data['log_data']=$details->change($self_id,$log_num);

            }


            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }

    }


}
?>
