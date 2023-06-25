<?php
namespace App\Http\Admin\Wms;

use App\Http\Controllers\CommonController;
use App\Models\Wms\InoutOtherMoney;
use App\Models\Wms\WmsGroup;
use App\Models\Wms\WmsSettleMoney;
use Illuminate\Http\Request;

class SettleController extends CommonController{

    /**
     * 结算收款单列表  wms/settle/settleList
     ***/
    public function settleList(Request $request){

    }

    public function settlePage(Request $request){

    }

    /***
     * 获取结算列表  wms/settle/createSettle
     **/
    public function createSettle(Request $request){

    }

    /**
     * 获取需要结算的订单 wms/settle/getSettleOrder
     * */
    public function getSettleOrder(Request $request){
        $group_code = $request->input('group_code');
        $company_id = $request->input('company_id');
        $where=[
            ['delete_flag','=','Y'],
            ['group_code','=',$group_code],
            ['self_id','=',$company_id],
        ];
        $where1=[
            ['delete_flag','=','Y'],
        ];
        $data['info']=WmsSettleMoney::where($where)->get();

        foreach($data['info'] as $key => $value){

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    /**
     * 添加结算收款  wms/settle/addSettle
     * */
    public function addSettle(Request $request){

    }









































































































}
