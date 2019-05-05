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

    /**
     * 新增合同
     *
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 合同应付到账信息
     *
     * @return mixed
     */
    public function expect_receipt()
    {
        if (!empty($_POST)) {
            dump($_POST);
            die;
        }

        return $this->fetch();
    }

    /**
     * 数据统计
     *
     * @return mixed
     */
    public function stat()
    {
        return $this->fetch();
    }

    /**
     * 应收信息
     *
     * @return mixed
     */
    public function expect()
    {
        return $this->fetch();
    }

    public function ajaxExpect()
    {
        $page = input('page', 1);
        $limit = input('limit', 10);
        for ($i = 0; $i < 10; $i++) {

            $data[] = [
                'expect_date' => date('Y-m-d'),
                'expect_amount' => '500000',
                'id' => ($page - 1) * $limit + $i,
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

    /**
     * 到账信息
     *
     * @return mixed
     */
    public function receipt()
    {
        return $this->fetch();
    }

    public function ajaxReceipt()
    {
        $page = input('page', 1);
        $limit = input('limit', 10);
        for ($i = 0; $i < 10; $i++) {

            $data[] = [
                'receipt_date' => date('Y-m-d'),
                'receipt_amount' => '500000',
                'receipt_type' => '现金',
                'ex_contract_no' => '',
                'expect_date' => date('Y-m-d'),
                'expect_amount' => '500000',
                'id' => ($page - 1) * $limit + $i,
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

    /**
     * 逾期信息
     *
     * @return mixed
     */
    public function overdue()
    {
        return $this->fetch();
    }

    public function ajaxOverdue()
    {
        $page = input('page', 1);
        $limit = input('limit', 10);
        for ($i = 0; $i < 10; $i++) {

            $data[] = [
                'overdue_date' => date('Y-m-d'),
                'overdue_amount' => '500000',
                'overdue_day' => '33',
                'id' => ($page - 1) * $limit + $i,
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
}
