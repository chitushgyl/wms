<?php
namespace App\Http\Admin\Wms;

use App\Http\Controllers\CommonController;
use App\Models\Wms\InoutOtherMoney;
use http\Env\Request;

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
        ];
        $where1=[
            ['delete_flag','=','Y'],
        ];
        $data['info']=InoutOtherMoney::with(['WmsDepositGood' => function($query)use($where1){
            $query->where($where1);
        }])
            ->with(['WmsLibrarySige' => function($query)use($where1){
                $query->where($where1);
            }])
            ->with(['WmsOutOrderList' => function($query)use($where1){
                $query->where($where1);
            }])
            ->with(['WmsChangeList' => function($query)use($where1){
                $query->where($where1);
            }])
            ->with(['WmsBulkGood' => function($query)use($where1){
                $query->where($where1);
            }])
            ->with(['TurnCardGood' => function($query)use($where1){
                $query->where($where1);
            }])
            ->with(['WmsHomework' => function($query)use($where1){
                $query->where($where1);
            }])
            ->with(['WmsSortingGood' => function($query)use($where1){
                $query->where($where1);
            }])
           ->where($where)->get();

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
