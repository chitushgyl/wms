<?php
namespace App\Http\Admin\Wms;
use App\Models\Wms\WmsLibrarySige;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Wms\WmsWarehouse;
use App\Models\Group\SystemGroup;

class WarehouseController extends CommonController{
    /***    仓库列表      /wms/warehouse/warehouseList
     */

    public function  warehouseList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $abc='仓库';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/仓库导入实例文件.xlsx',
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    仓库分页      /wms/warehouse/warehousePage
     */
    public function warehousePage(Request $request){

        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num                    =$request->input('num')??10;
        $page                   =$request->input('page')??1;
        $use_flag               =$request->input('use_flag');
        $group_code             =$request->input('group_code');
        $warehouse_name         =$request->input('warehouse_name');
        $pid                    =$request->input('pid');
        $all_weight             =$request->input('all_weight');
        $all_volume             =$request->input('all_volume');

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'like','name'=>'warehouse_name','value'=>$warehouse_name],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'pid','value'=>0],
        ];

        $where=get_list_where($search);

        $select=['id','self_id','warehouse_name','pid','all_weight','all_volume','city','warehouse_address','use_flag','group_code','group_name','warehouse_tel','warehouse_contacts','city'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsWarehouse::where($where)->count(); //总的数据量
                $data['items']=WmsWarehouse::with(['allChildren' => function($query)use($select) {
                    $query->select($select);
                    $query->where('delete_flag','Y');
                    $query->orderBy('id','asc');
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsWarehouse::where($where)->count(); //总的数据量
                $data['items']=WmsWarehouse::with(['allChildren' => function($query)use($select) {
                    $query->select($select);
                    $query->where('delete_flag','Y');
                    $query->orderBy('id','asc');
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsWarehouse::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=WmsWarehouse::with(['allChildren' => function($query)use($select) {
                    $query->select($select);
                    $query->where('delete_flag','Y');
                    $query->orderBy('id','asc');
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

//dd($data);
        foreach ($data['items'] as $k=>$v) {

            $v->button_info=$button_info;

        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    创建仓库     /wms/warehouse/createWarehouse
     */
    public function createWarehouse(Request $request){

        $self_id            =$request->input('self_id');
        //$self_id            ='ware_202006012159456407842832';
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $data['info']=WmsWarehouse::where($where)->first();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    创建仓库进入数据库      /wms/warehouse/addWarehouse
     */
    public function addWarehouse(Request $request){

        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_warehouse';

        $operationing->access_cause     ='创建/修改仓库';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $group_code         =$request->input('group_code');
        $children           =json_decode($request->input('children'),true);
        $pid                =$request->input('pid');

        /*** 虚拟数据
        $input['self_id']           =$self_id='good_202007011336328472133661';
        $input['group_code']        =$group_code='1234';
        $input['warehouse_name']    =$warehouse_name='优惠券名称';

         **/

        $rules=[
            'group_code'=>'required',
        ];
        $message=[
            'group_code.required'=>'请选择所属公司',
        ];
        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){

            /** 现在开始可以做数据了**/

            $where2['self_id'] = $self_id;
            $select_WmsWarehouse=['self_id','warehouse_name','pid','all_weight','all_volume','citycode','city','warehouse_address','warehouse_tel','warehouse_contacts','group_code',
                'group_name','remark'];
            $old_info=WmsWarehouse::where($where2)->select($select_WmsWarehouse)->first();
                $data['pid'] = $pid;
                $data['children'] = $children;
            if($old_info){
                $data['update_time'] =$now_time;
                $id = self::loop($children,$pid);

                $operationing->access_cause='修改仓库';
                $operationing->operation_type='update';
            }else{
                $wehre222['self_id']=$group_code;
                $group_name = SystemGroup::where($wehre222)->value('group_name');

//                $arr = [[
//                    'id'=>'',
//                    'pid'=>1,
//                    'warehouse_name'=>'徐汇',
//                    'all_weight'=>'40',
//                    'all_volume'=>'40',
//                    'remark'=>'40',
//                    'group_code'=>'1234',
//                    'group_name'=>'40',
//                    'create_user_id'=>'40',
//                    'create_user_name'=>'40',
//                    'all_children'=>[[
//                            'id'=>'',
//                            'pid'=>null,
//                            'warehouse_name'=>'徐家汇',
//                            'all_weight'=>'40',
//                            'all_volume'=>'40',
//                            'remark'=>'40',
//                            'group_code'=>'1234',
//                            'group_name'=>'40',
//                            'create_user_id'=>'40',
//                            'create_user_name'=>'40',
//                            'all_children'=>[],
//                        ]],
//                ],[
//                    'id'=>'',
//                    'pid'=>2,
//                    'warehouse_name'=>'南翔',
//                    'all_weight'=>'40',
//                    'all_volume'=>'40',
//                    'remark'=>'40',
//                    'group_code'=>'1234',
//                    'group_name'=>'40',
//                    'create_user_id'=>'40',
//                    'create_user_name'=>'40',
//                    'all_children'=>[],
//                ]];
                $id = self::loop($children,$pid);

                $operationing->access_cause='新建仓库';
                $operationing->operation_type='create';
            }
//            dd(123);
            $operationing->table_id=$old_info?$self_id:null;
            $operationing->old_info=$old_info;
            $operationing->new_info=(object)$data;

            if($id){
                $msg['code']=200;
                $msg['msg']='操作成功';
                $msg['data']=(object)$data;
                return $msg;
            }else{
                $msg['code']=303;
                $msg['msg']='操作失败';
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

    public function loop($arr,$insertid){
                $now_time = date('Y-m-d H:i:s');
            foreach ($arr as $k => $v){
                if ($v['id']){
                    $update['update_time'] = $now_time;
                    $update['warehouse_name'] = $v['warehouse_name'];
                    $update['all_weight']     = $v['all_weight'];
                    $update['all_volume']     = $v['all_volume'];
                    $update['remark']     = $v['remark'];
                    $id = WmsWarehouse::update($update);
                }else{
                    $data['self_id']            = generate_id('W');
                    if (isset($v['pid'])){
                        $data['pid']                = $v['pid'];
                    }else{
                        $data['pid']                = $insertid;
                    }
                    $data['warehouse_name']     = $v['warehouse_name'];
                    $data['all_weight']         = $v['all_weight'];
                    $data['all_volume']         = $v['all_volume'];
                    $data['remark']             = $v['remark'];
                    $data['group_code']         = $v['group_code'];
                    $data['group_name']         = $v['group_name'];
                    $data['create_user_id']     = $v['create_user_id'];
                    $data['create_user_name']   = $v['create_user_name'];
                    $data['create_time']  =  $data['update_time'] = $now_time;
                    $id = WmsWarehouse::insertGetId($data);

                }
                if (count($v['all_children'])>0){
                    self::loop($v['all_children'],$id);
                }else{
//                    break;
                }

            }
            return $id;

    }

    /**
     * 一键生成仓库库位 /wms/warehouse/warehouseSign
     * */
    public function warehouseSign(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_warehouse_sign';

        $operationing->access_cause     ='创建/修改库区';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $warehouse_id       =$request->input('warehouse_id');
        $row_left           =$request->input('row_left');
        $row_right          =$request->input('row_right');
        $tier_left          =$request->input('tier_left');
        $tier_right         =$request->input('tier_right');
        $column_left        =$request->input('column_left');
        $column_right       =$request->input('column_right');
        $pid                =$request->input('pid');

        /*** 虚拟数据
        //        $input['self_id']           =$self_id='good_202007011336328472133661';
        //        $input['area_id']           =$area_id='area_202011151042442192785461';
        $input['row_left']              =$row_left='1';
        $input['row_right']              =$row_right='1';
        $input['tier_left']              =$tier_left='1';
        $input['tier_right']              =$tier_right='4';
        $input['column_left']              =$column_left='1';
        $input['column_right']              =$column_right='12';
         * **/

//		DD($input);
        $rules=[
            'warehouse_id'=>'required',
        ];
        $message=[
            'warehouse_id.required'=>'请选择所属仓库',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()) {
            /** 第二步效验，left的值必须小于right 的 值*/
            if($row_left > $row_right){
                $msg['code']=301;
                $msg['msg']='库位排位1必须小于或等于库位排位2';
                return $msg;
            }

            if($tier_left > $tier_right){
                $msg['code']=302;
                $msg['msg']='库位层1必须小于或等于库位层2';
                return $msg;
            }

            if($column_left > $column_right){
                $msg['code']=303;
                $msg['msg']='库位列位1必须小于或等于库位列位2';
                return $msg;
            }


            //现在要根据这个做笛卡尔积,先得到几个数组
            $row        =$this->squares($row_left,$row_right);
            $tier       =$this->squares($tier_left,$tier_right);
            $column     =$this->squares($column_left,$column_right);

            //初始化一些变量
            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数

            foreach ($row as $k => $v){
                foreach ($column as $kk => $vv){
                    foreach ($tier as $kkk=>$vvv){
                        $sign['row']=$v;
                        $sign['column']=$vv;
                        $sign['tier']=$vvv;
                        $datalist[]=$sign;
                    }
                }
            }

            $info = WmsWarehouse::where('self_id',$warehouse_id)->first();
            foreach ($datalist as $k => $v){
                $where=[
                    ['sign_name','=',$v['row'].'-'.$v['column'].'-'.$v['tier']],
                    ['delete_flag','=','Y'],
                    ['warehouse_id','=',$warehouse_id],
                ];

                $exists=WmsWarehouseSign::where($where)->exists();
                if($exists){
                    $cando='N';
                    if($abcd<$errorNum){
                        $strs.='第'.$v['row'].'排，第'.$v['column'].'列，第'.$v['tier'].'层的'.'库位已存在'.'</br>';
                        $abcd++;
                    }

                    // dump($v);
                    //dd($strs);
                    //break;
                }

                $datalist[$k]['self_id']				=generate_id('sign_');
                $datalist[$k]['warehouse_id']			=$info->warehouse_id;
                $datalist[$k]['warehouse_name']			=$info->warehouse_name;
                $datalist[$k]['group_code']				=$info->group_code;
                $datalist[$k]['group_name']				=$info->group_name;
                $datalist[$k]['create_user_id']     	=$user_info->admin_id;
                $datalist[$k]['create_user_name']   	=$user_info->name;
                $datalist[$k]['create_time']			=$datalist[$k]['update_time']=$now_time;



            }
            if($cando=='Y'){
                $new_list = array_chunk($datalist,1000);
//                dd($new_list);
                foreach ($new_list as $value){
                    $id=WmsLibrarySige::insert($value);
                }
                if($id){
                    $msg['code'] = 200;
                    $msg['msg'] = "操作成功,创建了".count($datalist).'个库位';
                    //dd($msg);
                    return $msg;
                }else{
                    $msg['code'] = 302;
                    $msg['msg'] = "操作失败";
                    return $msg;
                }

            }else{
                $msg['code'] = 302;
                $msg['msg'] = $strs;
                return $msg;
            }

        }else{
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

    /***    仓库启用/禁用      /wms/warehouse/warehouseUseFlag
     */
    public function warehouseUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_warehouse';
        $medol_name='wmsWarehouse';
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

    /***    仓库删除      /wms/warehouse/warehouseDelFlag
     */
    public function warehouseDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_warehouse';
        $medol_name='wmsWarehouse';
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


    /***    获取仓库      /wms/warehouse/getWarehouse
     */
    public function getWarehouse(Request $request){
        $group_code            =$request->input('group_code');
        $where=[
            ['delete_flag','=','Y'],
            ['group_code','=',$group_code],
            ['pid','=',0],
        ];

	    $select=['id','self_id','pid','warehouse_name','group_code','group_name','delete_flag','use_flag'];

        $data['info']=WmsWarehouse::with(['allChildren' => function($query)use($select) {
            $query->select($select);
            $query->where('delete_flag','Y');
            $query->orderBy('id','asc');
        }])->where($where)
            ->orderBy('id', 'asc')
            ->select($select)->get();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        return $msg;

    }
    /***    仓库导入     /wms/warehouse/import
     */
    public function import(Request $request){
        $table_name         ='wms_warehouse';
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建仓库';
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
        $input['importurl']    =$importurl="uploads/2020-11-11/仓库导入实例文件.xlsx";
        $input['group_code']   =$group_code='1234';***/

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
                '仓库名称' =>['Y','N','64','warehouse_name'],
                '联系人' =>['N','Y','255','warehouse_contacts'],
                '联系电话' =>['N','Y','50','warehouse_tel'],
                '详细地址' =>['N','Y','255','warehouse_address'],
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
                ['group_code','=',$group_code],
            ];
            $group_name = SystemGroup::where($where_check)->value('group_name');

            if(empty($group_name)){
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

			//dd($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                $where=[
                    ['delete_flag','=','Y'],
                    ['warehouse_name','=',$v['warehouse_name']],
                    ['group_code','=',$group_code],
                ];

                $pack_info = WmsWarehouse::where($where)->value('group_code');

                if($pack_info){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行包装已存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('warehouse_');
                    $list['warehouse_name']     = $v['warehouse_name'];
                    $list['warehouse_contacts'] = $v['warehouse_contacts'];
                    $list['warehouse_tel']     	= $v['warehouse_tel'];
                    $list['warehouse_address']  = $v['warehouse_address'];
                    $list['group_code']         = $group_code;
                    $list['group_name']         = $group_name;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
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
            $id= WmsWarehouse::insert($datalist);

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

    /***    仓库详情     /wms/warehouse/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='wms_warehouse';
        $select=['self_id','group_code','group_name','use_flag','create_user_name','create_time',
            'warehouse_name','warehouse_address','warehouse_contacts','warehouse_tel'];
        $info=$details->details($self_id,$table_name,$select);

        if($info){

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/


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
