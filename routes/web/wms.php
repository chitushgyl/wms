<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/29
 * Time: 13:23
 */

/******WMS系统*******/
Route::group([
    'prefix' => 'wms',
    "middleware"=>['loginCheck','group'],
    'namespace'  => 'Wms',
], function(){
    /**包装设置**/
    Route::any('/pack/packList', 'PackController@packList');
    Route::any('/pack/packPage', 'PackController@packPage');
	Route::any('/pack/getPack', 'PackController@getPack');
    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/pack/import', 'PackController@import');
        Route::any('/pack/addPack', 'PackController@addPack');
        Route::any('/pack/packUseFlag', 'PackController@packUseFlag');
        Route::any('/pack/packDelFlag', 'PackController@packDelFlag');
    });

    /**仓库设置**/
    Route::any('/warehouse/warehouseList', 'WarehouseController@warehouseList');
    Route::any('/warehouse/warehousePage', 'WarehouseController@warehousePage');
    Route::any('/warehouse/createWarehouse', 'WarehouseController@createWarehouse');
    Route::any('/warehouse/getWarehouse', 'WarehouseController@getWarehouse');
    Route::any('/warehouse/warehouseSign', 'WarehouseController@warehouseSign');
    Route::any('/warehouse/addWarehouse', 'WarehouseController@addWarehouse');
    Route::any('/warehouse/details', 'WarehouseController@details');

    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/warehouse/import', 'WarehouseController@import');

        Route::any('/warehouse/warehouseUseFlag', 'WarehouseController@warehouseUseFlag');
        Route::any('/warehouse/warehouseDelFlag', 'WarehouseController@warehouseDelFlag');
    });


    /**温区设置**/
    Route::any('/warm/warmList', 'WarmController@warmList');
    Route::any('/warm/warmPage', 'WarmController@warmPage');
    Route::any('/warm/createWarm', 'WarmController@createWarm');
    Route::any('/warm/getWarm', 'WarmController@getWarm');
    Route::any('/warm/import', 'WarmController@import');
    Route::any('/warm/details', 'WarmController@details');
    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/warm/addWarm', 'WarmController@addWarm');
        Route::any('/warm/warmUseFlag', 'WarmController@warmUseFlag');
        Route::any('/warm/warmDelFlag', 'WarmController@warmDelFlag');
    });


    /**库区设置**/
    Route::any('/area/areaList', 'AreaController@areaList');
    Route::any('/area/areaPage', 'AreaController@areaPage');
    Route::any('/area/createArea', 'AreaController@createArea');

	Route::any('/area/getArea', 'AreaController@getArea');
    Route::any('/area/details', 'AreaController@details');
    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/area/import', 'AreaController@import');
        Route::any('/area/addArea', 'AreaController@addArea');
        Route::any('/area/areaUseFlag', 'AreaController@areaUseFlag');
        Route::any('/area/areaDelFlag', 'AreaController@areaDelFlag');
    });

    /**库位管理**/
    Route::any('/sign/signList', 'SignController@signList');
    Route::any('/sign/signPage', 'SignController@signPage');

    Route::any('/sign/getSign', 'SignController@getSign');
    Route::any('/sign/details', 'SignController@details');
    Route::any('/sign/addSign', 'SignController@addSign');
    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/sign/import', 'SignController@import');

        Route::any('/sign/signUseFlag', 'SignController@signUseFlag');
        Route::any('/sign/signDelFlag', 'SignController@signDelFlag');
    });


    /**业务往来公司管理**/
    Route::any('/group/groupList', 'GroupController@groupList');
    Route::any('/group/groupPage', 'GroupController@groupPage');
    Route::any('/group/addGroup', 'GroupController@addGroup');
    Route::any('/group/createGroup', 'GroupController@createGroup');
	Route::any('/group/getCompany', 'GroupController@getCompany');
    Route::any('/group/execl', 'GroupController@execl');
    Route::any('/group/details', 'GroupController@details');
	Route::any('/group/import', 'GroupController@import');
    Route::group([
        "middleware"=>['daily'],
    ], function(){


        Route::any('/group/groupUseFlag', 'GroupController@groupUseFlag');
        Route::any('/group/groupDelFlag', 'GroupController@groupDelFlag');
    });



    /**商品管理**/
    Route::any('/good/goodList', 'GoodController@goodList');
    Route::any('/good/goodPage', 'GoodController@goodPage');
    Route::any('/good/createGood', 'GoodController@createGood');
    Route::any('/good/execl', 'GoodController@execl');
    Route::any('/good/getGood', 'GoodController@getGood');
    Route::any('/good/details', 'GoodController@details');
    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/good/import', 'GoodController@import');
        Route::any('/good/addGood', 'GoodController@addGood');
        Route::any('/good/goodUseFlag', 'GoodController@goodUseFlag');
        Route::any('/good/goodDelFlag', 'GoodController@goodDelFlag');
    });

    /**配送门店管理**/
    Route::any('/shop/shopList', 'ShopController@shopList');
    Route::any('/shop/shopPage', 'ShopController@shopPage');
    Route::any('/shop/createShop', 'ShopController@createShop');

    Route::any('/shop/execl', 'ShopController@execl');
    Route::any('/shop/details', 'ShopController@details');
	Route::any('/shop/getShop', 'ShopController@getShop');

    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/shop/import', 'ShopController@import');
        Route::any('/shop/addShop', 'ShopController@addShop');
        Route::any('/shop/shopDelFlag', 'ShopController@shopDelFlag');
        Route::any('/shop/shopUseFlag', 'ShopController@shopUseFlag');
    });

    /**拆包管理**/
    Route::any('/unpack/unpackList', 'UnpackController@unpackList');             //拆包列表
    Route::any('/unpack/unpackPage', 'UnpackController@unpackPage');            //拆包列表分页
    Route::any('/unpack/load_shop', 'UnpackController@load_shop');              //抓取商品
    Route::any('/unpack/unpack_group', 'UnpackController@unpack_group');
    Route::any('/unpack/addUnpack', 'UnpackController@addUnpack');               //拆包规则提交



    /**入库及审核**/
    Route::any('/library/libraryList','LibraryController@libraryList');
    Route::any('/library/libraryPage','LibraryController@libraryPage');

    Route::any('/library/createLibrary', 'LibraryController@createLibrary');
    Route::any('/library/libraryCheck', 'LibraryController@libraryCheck');
    Route::any('/library/getLibrary', 'LibraryController@getLibrary');
    Route::any('/library/details', 'LibraryController@details');
    Route::any('/library/import','LibraryController@import');
    Route::any('/library/wait_library','LibraryController@wait_library');
    Route::any('/library/editSku','LibraryController@editSku');
    Route::any('/library/grounding','LibraryController@grounding');
    Route::any('/library/delSku','LibraryController@delSku');
    Route::any('/library/addLibrary', 'LibraryController@addLibrary');
    Route::any('/library/libraryNlist', 'LibraryController@libraryNlist');
    Route::any('/library/delLibraryOrder', 'LibraryController@delLibraryOrder');
    Route::any('/library/freeLibrarySku', 'LibraryController@freeLibrarySku');
    Route::any('/library/cancelGrounding', 'LibraryController@cancelGrounding');
    Route::any('/library/createLibrary', 'LibraryController@createLibrary');
    Route::any('/library/getLibrarySige', 'LibraryController@getLibrarySige');
    Route::any('/library/editLibrary', 'LibraryController@editLibrary');
    Route::group([
        "middleware"=>['daily'],
    ], function(){


		Route::any('/library/checkStatus', 'LibraryController@checkStatus');
    });


    /**库位商品查询**/
    Route::any('/search/searchList', 'SearchController@searchList');
    Route::any('/search/searchPage', 'SearchController@searchPage');
    Route::any('/search/createMove', 'SearchController@createMove');
    Route::any('/search/details', 'SearchController@details');
    Route::any('/search/mistakeRevise', 'SearchController@mistakeRevise');
    Route::any('/search/addMove', 'SearchController@addMove');
    Route::group([
        "middleware"=>['daily'],
    ], function(){

    });


    /**盘点库存**/
    Route::any('/check/checkList', 'CheckController@checkList');
    Route::any('check/checkPage', 'CheckController@checkPage');
	Route::any('check/addCheck', 'CheckController@addCheck');
	Route::any('check/details', 'CheckController@details');
    Route::group([
        "middleware"=>['daily'],
    ], function(){

    });

    /**出库订单管理**/
    Route::any('/order/orderList', 'OrderController@orderList');
    Route::any('/order/orderPage', 'OrderController@orderPage');
    Route::any('/order/print', 'OrderController@print');
    Route::any('/order/details', 'OrderController@details');
    Route::any('/order/getOrder', 'OrderController@getOrder');
    Route::any('/order/statusOrder','OrderController@statusOrder');
    Route::any('/order/delOutOrder','OrderController@delOutOrder');
	Route::any('/order/import', 'OrderController@import');
	Route::any('/order/outOrder', 'OrderController@outOrder');
	Route::any('/order/outOrderDone', 'OrderController@outOrderDone');
	Route::any('/order/addOutorderSku', 'OrderController@addOutorderSku');
	Route::any('/order/delOutorderSku', 'OrderController@delOutorderSku');
	Route::any('/order/createOrder', 'OrderController@createOrder');
	Route::any('/order/editOrder', 'OrderController@editOrder');
    Route::group([
        "middleware"=>['daily'],
    ], function(){



    });


    /**总拣管理**/
    Route::any('/total/totalList', 'TotalController@totalList');
    Route::any('/total/totalPage', 'TotalController@totalPage');
    Route::any('/total/createTotal','TotalController@createTotal');
    Route::any('/total/details', 'TotalController@details');
    Route::any('/total/orderPrint', 'TotalController@orderPrint');
    Route::any('/total/addTotal', 'TotalController@addTotal');
    Route::group([
        "middleware"=>['daily'],
    ], function(){

    });


    /**商品出入库记录**/
    Route::any('/history/historyList', 'HistoryController@historyList');
    Route::any('/history/historyPage', 'HistoryController@historyPage');


    /**商品查询**/
    Route::any('/goodquery/goodqueryList', 'GoodqueryController@goodqueryList');
    Route::any('/goodquery/goodqueryPage', 'GoodqueryController@goodqueryPage');

    /**库位查询**/
    Route::any('/warehousequery/warehousequeryList', 'WarehousequeryController@warehousequeryList');
    Route::any('/warehousequery/warehousequeryPage', 'WarehousequeryController@warehousequeryPage');
    Route::any('/warehousequery/details', 'WarehousequeryController@details');




    /**WMS费用规则管理**/
    Route::any('/cost/costList', 'CostController@costList');
    Route::any('/cost/costPage', 'CostController@costPage');
    Route::any('/cost/createCost','CostController@createCost');
    Route::any('/cost/details', 'CostController@details');
    Route::any('/cost/getCost','CostController@getCost');
    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/cost/addCost','CostController@addCost');
    });

    /**WMS费用明细管理**/
    Route::any('/money/moneyList', 'MoneyController@moneyList');
    Route::any('/money/moneyPage', 'MoneyController@moneyPage');
    Route::any('/money/details','MoneyController@details');
    Route::any('/money/paymentCheck', 'MoneyController@paymentCheck');//付款方确认
    Route::any('/money/payeeCheck', 'MoneyController@payeeCheck');//收款方确认
    Route::group([
        "middleware"=>['daily'],
    ], function(){

    });


    /**WMS费用结算管理**/
    Route::any('/settle/settleList', 'SettleController@settleList');
    Route::any('/settle/settlePage', 'SettleController@settlePage');
	Route::any('/settle/createSettle','SettleController@createSettle');
    Route::any('/settle/addSettle', 'SettleController@addSettle');
    Route::any('/settle/details','SettleController@details');
	Route::any('/settle/createGathering','SettleController@createGathering');
    Route::any('/settle/addGathering','SettleController@addGathering');
    Route::any('/settle/updateMoney','SettleController@updateMoney');
    Route::any('/settle/getSettleOrder','SettleController@getSettleOrder');

    Route::any('/deposit/depositList', 'DepositController@depositList');
    Route::any('/deposit/depositPage', 'DepositController@depositPage');
    Route::any('/deposit/createDeposit','DepositController@createDeposit');
    Route::any('/deposit/addDeposit', 'DepositController@addDeposit');
    Route::any('/deposit/depositDelFlag','DepositController@depositDelFlag');


    Route::any('/change/changeList', 'ChangeController@changeList');
    Route::any('/change/changePage', 'ChangeController@changePage');
    Route::any('/change/createChange','ChangeController@createChange');
    Route::any('/change/addChange', 'ChangeController@addChange');
    Route::any('/change/changeDelFlag','ChangeController@changeDelFlag');
    Route::any('/change/updateChange','ChangeController@updateChange');

    Route::any('/turn/turnList', 'TurnController@turnList');
    Route::any('/turn/turnPage', 'TurnController@turnPage');
    Route::any('/turn/createTurn','TurnController@createTurn');
    Route::any('/turn/addTurn', 'TurnController@addTurn');
    Route::any('/turn/turnDelFlag','TurnController@turnDelFlag');
    Route::any('/turn/updateTurn','TurnController@updateTurn');

    Route::any('/homework/homeworkList', 'HomeworkController@homeworkList');
    Route::any('/homework/homeworkPage', 'HomeworkController@homeworkPage');
    Route::any('/homework/createHomework','HomeworkController@createHomework');
    Route::any('/homework/addHomework', 'HomeworkController@addHomework');
    Route::any('/homework/homeworkDelFlag','HomeworkController@homeworkDelFlag');

    Route::any('/sorting/sortingList', 'SortingController@sortingList');
    Route::any('/sorting/sortingPage', 'SortingController@sortingPage');
    Route::any('/sorting/createSorting','SortingController@createSorting');
    Route::any('/sorting/addSorting', 'SortingController@addSorting');
    Route::any('/sorting/SortingDelFlag','SortingController@SortingDelFlag');

    Route::any('/labour/labourList', 'LabourController@labourList');
    Route::any('/labour/labourPage', 'LabourController@labourPage');
    Route::any('/labour/createLabour','LabourController@createLabour');
    Route::any('/labour/addLabour', 'LabourController@addLabour');
    Route::any('/labour/LabourDelFlag','LabourController@LabourDelFlag');


    Route::any('/bulkContract/bulkContractList', 'BulkContractController@bulkContractList');
    Route::any('/bulkContract/bulkContractPage', 'BulkContractController@bulkContractPage');
    Route::any('/bulkContract/createBulkContract','BulkContractController@createBulkContract');
    Route::any('/bulkContract/addBulkContract', 'BulkContractController@addBulkContract');
    Route::any('/bulkContract/bulkContractDelFlag','BulkContractController@bulkContractDelFlag');

    Route::any('/logistic/logisticList', 'LogisticController@logisticList');
    Route::any('/logistic/logisticPage', 'LogisticController@logisticPage');
    Route::any('/logistic/createLogistic','LogisticController@createLogistic');
    Route::any('/logistic/addLogistic', 'LogisticController@addLogistic');
    Route::any('/logistic/logisticDelFlag','LogisticController@logisticDelFlag');
    Route::any('/logistic/getLogistic','LogisticController@getLogistic');

    Route::any('/carType/carTypeList', 'CarTypeController@carTypeList');
    Route::any('/carType/carTypePage', 'CarTypeController@carTypePage');
    Route::any('/carType/createCarType','CarTypeController@createCarType');
    Route::any('/carType/addCarType', 'CarTypeController@addCarType');
    Route::any('/carType/carTypeDelFlag','CarTypeController@carTypeDelFlag');
    Route::any('/carType/getCarType','CarTypeController@getCarType');


    Route::any('/send/sendList','SendController@sendList');
    Route::any('/send/sendPage','SendController@sendPage');
    Route::any('/send/createSend','SendController@createSend');
    Route::any('/send/addSend','SendController@addSend');
    Route::any('/send/sendDelFlag','SendController@sendDelFlag');

    Route::any('/loading/loadingList', 'LoadingController@loadingList');
    Route::any('/loading/loadingPage', 'LoadingController@loadingPage');
    Route::any('/loading/createLoading','LoadingController@createLoading');
    Route::any('/loading/addLoading', 'LoadingController@addLoading');
    Route::any('/loading/loadingDelFlag','LoadingController@loadingDelFlag');

    Route::any('/crondtab/updateSkuState','CrondtabController@updateSkuState');
});


/******系统设置模块*******/
Route::group([
    'prefix' => 'more',"middleware"=>['loginCheck','group'],'namespace'=> 'More',
], function(){
    /**菜单设置**/
    Route::any('/billType/billTypeList', 'BillTypeController@billTypeList');
    Route::any('/billType/billTypePage', 'BillTypeController@billTypePage');
    Route::any('/billType/createBillType', 'BillTypeController@createBillType');
    Route::any('/billType/addBillType', 'BillTypeController@addBillType');
    Route::any('/billType/delBillType', 'BillTypeController@delBillType');
    Route::any('/billType/getBillType', 'BillTypeController@getBillType');


    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/menu/addMenu', 'MenuController@addMenu');
        Route::any('/menu/menuUseFlag', 'MenuController@menuUseFlag');
    });


    Route::any('/costType/costTypeList', 'CostTypeController@costTypeList');
    Route::any('/costType/costTypePage', 'CostTypeController@costTypePage');
    Route::any('/costType/createCostType', 'CostTypeController@createCostType');
    Route::any('/costType/addCostType', 'CostTypeController@addCostType');
    Route::any('/costType/delCostType', 'CostTypeController@delCostType');
    Route::any('/costType/getCostType', 'CostTypeController@getCostType');


    Route::any('/settleType/settleTypeList', 'SettleTypeController@settleTypeList');
    Route::any('/settleType/settleTypePage', 'SettleTypeController@settleTypePage');
    Route::any('/settleType/createSettleType', 'SettleTypeController@createSettleType');
    Route::any('/settleType/addSettleType', 'SettleTypeController@addSettleType');
    Route::any('/settleType/delSettleType', 'SettleTypeController@delSettleType');


    Route::any('/customerBank/customerBankList', 'CustomerBankController@customerBankList');
    Route::any('/customerBank/customerBankPage', 'CustomerBankController@customerBankPage');
    Route::any('/customerBank/createCustomerBank', 'CustomerBankController@createCustomerBank');
    Route::any('/customerBank/addCustomerBank', 'CustomerBankController@addCustomerBank');
    Route::any('/customerBank/delCustomerBank', 'CustomerBankController@delCustomerBank');


    Route::any('/wmsPorter/wmsPorterList', 'WmsPorterController@wmsPorterList');
    Route::any('/wmsPorter/wmsPorterPage', 'WmsPorterController@wmsPorterPage');
    Route::any('/wmsPorter/createWmsPorter', 'WmsPorterController@createWmsPorter');
    Route::any('/wmsPorter/addWmsPorter', 'WmsPorterController@addWmsPorter');
    Route::any('/wmsPorter/delWmsPorter', 'WmsPorterController@delWmsPorter');
    Route::any('/wmsPorter/getPorter', 'WmsPorterController@getPorter');

    Route::any('/wmsRailway/wmsRailwayList', 'WmsRailwayController@wmsRailwayList');
    Route::any('/wmsRailway/wmsRailwayPage', 'WmsRailwayController@wmsRailwayPage');
    Route::any('/wmsRailway/createWmsRailway', 'WmsRailwayController@createWmsRailway');
    Route::any('/wmsRailway/addWmsRailway', 'WmsRailwayController@addWmsRailway');
    Route::any('/wmsRailway/delWmsRailway', 'WmsRailwayController@delWmsRailway');
    Route::any('/wmsRailway/getRailway', 'WmsRailwayController@getRailway');

});


/******系统设置模块*******/
Route::group([
    'prefix' => 'base',"middleware"=>['loginCheck','group'],'namespace'=> 'Base',
], function(){
    /**菜单设置**/
    Route::any('/contract/contractList', 'ContractController@contractList');
    Route::any('/contract/contractPage', 'ContractController@contractPage');
    Route::any('/contract/createContract', 'ContractController@createContract');
    Route::any('/contract/addContract', 'ContractController@addContract');
    Route::any('/contract/ContractUseFlag', 'ContractController@ContractUseFlag');
    Route::any('/contract/ContractDelFlag', 'ContractController@ContractDelFlag');
    Route::any('/contract/getContract', 'ContractController@getContract');


    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/menu/addMenu', 'MenuController@addMenu');
        Route::any('/menu/menuUseFlag', 'MenuController@menuUseFlag');
    });





});


