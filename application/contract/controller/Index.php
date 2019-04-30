<?php

namespace app\contract\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function ajaxIndex()
    {
        $page = input('page', 1);
        $limit = input('limit', 10);
        for ($i = 0; $i < 10; $i++) {

            $data[] = [
                'contract_id' => ($page - 1) * $limit + $i,
                'launch_time' => 'xxxxx',
                'contract_no' => 'xxxxx',
                'erp_contract_no' => 'xxxxx',
                'customer' => 'xxxxx',
                'final_customer' => 'xxxxx',
                'agency' => 'xxxxx',
                'agency_type' => 'xxxxx',
                'mian_brand' => 'xxxxx',
                'brand' => 'xxxxx',
                'brand_type' => 'xxxxx',
                'channel' => 'xxxxx',
                'channel_manager' => 'xxxxx',
                'start_time' => 'xxxxx',
                'end_time' => 'xxxxx',
                'price' => 'xxxxx',
                'put_volume' => 'xxxxx',
                'amount' => 'xxxxx',
                'final_amount' => 'xxxxx',
                'balance_amount' => 'xxxxx',
                'balance' => 'xxxxx',
                'ad_type' => 'xxxxx',
            ];
        }
        exit(json_encode(['code' => 0, 'count' => 1000, 'data' => $data], JSON_UNESCAPED_UNICODE));
    }

    public function add()
    {
        return $this->fetch();
    }
}
