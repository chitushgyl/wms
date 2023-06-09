<?php
namespace App\Http\Admin\Wms;
use App\Models\Shop\ErpShopGoodsSku;
use App\Models\Wms\CompanyContact;
use App\Models\Wms\ContactAddress;
use App\Models\Wms\InoutOtherMoney;
use App\Models\Wms\WmsDeposit;
use App\Models\Wms\WmsDepositGood;
use App\Models\Wms\WmsLoading;
use App\Models\Wms\WmsLoadingList;
use App\Models\Wms\WmsSend;
use App\Models\Wms\WmsSendGood;
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

class LoadingController extends CommonController{
    /***    业务公司列表      /wms/loading/loadingList
     */
    public function  loadingList(Request $request){
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
    /***    业务公司分页      /wms/loading/loadingPage
     */
    public function loadingPage(Request $request){
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
        $select=['self_id','company_name','use_flag','group_name','company_id','deposit_time','car_number','company_num','group_code','create_time','update_time',
            'remark','contract_id','contract_num','porter_id','group_code','porter','total_weight','total_plate','total_price'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsLoading::where($where)->count(); //总的数据量
                $data['items']=WmsLoading::with(['WmsLoadingList' => function($query)use($where1){
                    $query->where($where1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('create_time', 'desc')
//                    ->select($select)
                    ->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsLoading::where($where)->count(); //总的数据量
                $data['items']=WmsLoading::with(['WmsLoadingList' => function($query)use($where1){
                    $query->where($where1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('create_time', 'desc')
//                    ->select($select)
                    ->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsLoading::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=WmsLoading::with(['WmsLoadingList' => function($query)use($where1){
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

    /***    业务公司创建      /wms/loading/createLoading
     */
    public function createLoading(Request $request){

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
        $data['info']=WmsLoading::with(['WmsLoadingList' => function($query)use($where1){
            $query->where($where1);
            $query->orderBy('sort','asc');
        }])->where($where)->first();
        if($data['info']){

        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;


    }

    /***    业务公司添加进入数据库      /wms/loading/addLoading
     */
    public function addLoading(Request $request){
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
        $logistic_id        =$request->input('logistic_id');//物流公司
        $logistic           =$request->input('logistic');//物流公司
        $car_id             =$request->input('car_id');//车型
        $car_type           =$request->input('car_type');//车型
        $car_number         =$request->input('car_number');//车牌号
        $total_num          =$request->input('total_num');//总费用
        $total_weight       =$request->input('total_weight');//总吨重
        $send_num           =$request->input('send_num');//送货件数
        $remark             =$request->input('remark');//备注
        $loading_time       =$request->input('loading_time');//时间
        $good_list          =json_decode($request->input('good_list'),true);


        $rules=[
            'group_code'=>'required',
        ];
        $message=[
            'group_code.required'=>'所属公司不能为空',
        ];
        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){

            $deposit_id                         =  generate_id('ZC');
            $data['logistic_id']                = $logistic_id;
            $data['logistic']                   = $logistic;
            $data['car_id']                     = $car_id;
            $data['car_type']                   = $car_type;
            $data['car_number']                 = $car_number;
            $data['total_num']                  = $total_num;
            $data['total_weight']               = $total_weight;
            $data['send_num']                   = $send_num;
            $data['remark']                 	= $remark;
            $data['loading_time']               = $loading_time;

            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;
            $deposit_list = [];
            DB::beginTransaction();
            try{
                foreach($good_list as $key => $value){
//                $list['self_id']           =  generate_id('DG');
                    $list['send_number']       =  $value['order_number'];//配送编号
                    $list['address']           =  $value['receive_address'];//收货地址
                    $list['sort']              =  $value['sort'];//排序
                    $list['good_info']         =  $value['good_info'];//商品信息
                    $list['delete_flag']       =  $value['delete_flag'];
                    if ($self_id){
                        $list['loading_id']           =  $self_id;//
                    }else{
                        $list['loading_id']           =  $deposit_id;//
                    }
                    if($value['self_id']){
                        $list['update_time']  = $now_time;
                        WmsLoadingList::where('self_id',$value['self_id'])->update($list);
                    }else{
                        $list["self_id"]            =generate_id('ZG');
                        $list["group_code"]         =$group_code;
                        $list["group_name"]         =$user_info->group_name;
                        $list['create_time']        =$now_time;
                        $list["update_time"]        =$now_time;
                        $list['create_user_id']     = $user_info->admin_id;
                        $list['create_user_name']   = $user_info->name;
                        $deposit_list[] = $list;
                    }

                    $a++;
                }


                $wheres['self_id'] = $self_id;
                $old_info=WmsLoading::where($wheres)->first();

                if($old_info){
                    $data['update_time'] = $now_time;
                    $id = WmsLoading::where('self_id',$self_id)->update($data);
                    WmsLoadingList::insert($deposit_list);
                    $operationing->access_cause='修改业务公司';
                    $operationing->operation_type='update';

                }else{

                    $data['self_id']=$deposit_id;		//优惠券表ID
                    $data['group_code'] = $group_code;
                    $data['group_name'] = SystemGroup::where('group_code','=',$group_code)->value('group_name');
                    $data['create_user_id']=$user_info->admin_id;
                    $data['create_user_name']=$user_info->name;
                    $data['create_time']=$data['update_time']=$now_time;
                    $id=WmsLoading::insert($data);

                    if ($id){
                        WmsLoadingList::insert($deposit_list);
                    }


                    $operationing->access_cause='新建业务公司';
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

    /***    业务公司启用禁用      /wms/loading/LoadingUseFlag
     */
    public function LoadingUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_loading';
        $medol_name='WmsLoading';
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

    /***    业务公司删除      /wms/loading/LoadingDelFlag
     */
    public function LoadingDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_loading';
        $medol_name='WmsLoading';
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
