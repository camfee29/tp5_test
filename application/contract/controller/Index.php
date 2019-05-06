<?php

namespace app\contract\controller;

use think\Controller;
use think\Db;

class Index extends Controller
{
    public function index()
    {
        $param = $_GET;
        $this->assign('param', $param);

        return $this->fetch();
    }

    public function ajaxIndex()
    {
        $page = input('page', 1);
        $limit = input('limit', 10);
        $where = [];
        $contract_no = input('contract_no');
        if (!empty($contract_no)) {
            $where['contract_no'] = $contract_no;
        }
        $erp_contract_no = input('erp_contract_no');
        if (!empty($erp_contract_no)) {
            $where['erp_contract_no'] = $erp_contract_no;
        }
        $customer = input('customer');
        if (!empty($customer)) {
            $where['customer'] = $customer;
        }
        $agency = input('agency');
        if (!empty($agency)) {
            $where['agency'] = $agency;
        }
        $brand = input('brand');
        if (!empty($brand)) {
            $where['brand'] = $brand;
        }
        $direct_group = input('direct_group');
        if (!empty($direct_group)) {
            $where['direct_group'] = $direct_group;
        }
        $direct_manager = input('direct_manager');
        if (!empty($direct_manager)) {
            $where['direct_manager'] = $direct_manager;
        }
        $ad_type = input('ad_type');
        if (!empty($ad_type)) {
            $where['ad_type'] = $ad_type;
        }
        $channel = input('channel');
        if (!empty($channel)) {
            $where['channel'] = $channel;
        }
        $channel_manager = input('channel_manager');
        if (!empty($channel_manager)) {
            $where['channel_manager'] = $channel_manager;
        }
        $start_time = input('start_time');
        if (!empty($start_time)) {
            $where['launch_time'][] = ['>=', strtotime($start_time)];
        }
        $end_time = input('end_time');
        if (!empty($end_time)) {
            $where['launch_time'][] = ['<=', strtotime($end_time)];
        }
        $count = Db::name('contract')->where($where)->count();
        $offset = ($page - 1) * $limit;
        $data = Db::name('contract')->where($where)->limit($offset, $limit)->select();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['launch_time'] = date('Y-m-d', $v['launch_time']);
                $data[$k]['start_time'] = date('Y-m-d', $v['start_time']);
                $data[$k]['end_time'] = date('Y-m-d', $v['end_time']);
            }
        }
        exit(json_encode(['code' => 0, 'count' => $count, 'data' => $data]));
    }

    /**
     * 新增合同
     *
     * @return mixed
     */
    public function add()
    {
        $id = input('id', 0, 'intval');
        $info = Db::name('contract')->where('contract_id', $id)->find();
        if (!empty($info)) {
            $info['launch_time'] = date('Y-m-d', $info['launch_time']);
            $info['start_time'] = date('Y-m-d', $info['start_time']);
            $info['end_time'] = date('Y-m-d', $info['end_time']);
        }
        $direct = Db::name('contract_direct')->where('contract_id', $id)->select();
        $this->assign('direct', $direct);
        $this->assign('info', $info);

        return $this->fetch();
    }

    public function save()
    {
        $cid = input('contract_id', 0, 'intval');
        $data = $_POST;
        $ids = $data['id'];
        $direct_group = $data['direct_group'];
        $direct_manager = $data['direct_manager'];
        $rate = $data['rate'];
        unset($data['contract_id'], $data['id'], $data['direct_group'], $data['direct_manager'], $data['rate']);
        $data['launch_time'] = strtotime($data['launch_time']);
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        if (!empty($cid)) {
            // 修改
            $data['update_time'] = time();
            $res = Db::name('contract')->where('contract_id', $cid)->update($data);
        } else {
            // 新增
            $data['add_time'] = time();
            $res = $cid = Db::name('contract')->insertGetId($data);
        }
        $direct_update = $direct_add = [];
        if (!empty($ids)) {
            foreach ($ids as $k => $id) {
                if (!empty($id)) {
                    $direct_update[$id] = [
                        'contract_id' => $cid,
                        'direct_group' => trim($direct_group[$k]),
                        'direct_manager' => trim($direct_manager[$k]),
                        'rate' => (float)$rate[$k],
                        'update_time' => time(),
                    ];
                } else {
                    $direct_add[] = [
                        'contract_id' => $cid,
                        'direct_group' => trim($direct_group[$k]),
                        'direct_manager' => trim($direct_manager[$k]),
                        'rate' => (float)$rate[$k],
                        'add_time' => time(),
                    ];
                }
            }
        }
        if (!empty($direct_update)) {
            foreach ($direct_update as $id => $val) {
                Db::name('contract_direct')->where('id', $id)->update($val);
            }
        }
        if (!empty($direct_add)) {
            Db::name('contract_direct')->insertAll($direct_add);
        }
        if ($res !== false) {
            $this->success('操作成功', url('index'));
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 合同应付到账信息
     *
     * @return mixed
     */
    public function expect_receipt()
    {
        $contract_id = input('contract_id', 0, 'intval');
        if (!empty($_POST)) {
            dump($_POST);
            die;
        }
        $expect = Db::name('contract_expect')->where('contract_id', $contract_id)->select();
        $receipt = Db::name('contract_receipt')->where('contract_id', $contract_id)->select();

        $this->assign('expect', $expect);
        $this->assign('receipt', $receipt);

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

    /**
     * 结算余额
     *
     * @return mixed
     */
    public function balance()
    {
        return $this->fetch();
    }

    public function ajaxBalance()
    {
        $page = input('page', 1);
        $limit = input('limit', 10);
        for ($i = 0; $i < 10; $i++) {

            $data[] = [
                'balance_amount' => '600000',
                'balance' => '500000',
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
                'ad_type' => 'xxxxx',
            ];
        }
        exit(json_encode(['code' => 0, 'count' => 1000, 'data' => $data], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 代理服务费
     *
     * @return mixed
     */
    public function agency_fee()
    {
        return $this->fetch();
    }

    public function ajaxAgencyFee()
    {
        $page = input('page', 1);
        $limit = input('limit', 10);
        for ($i = 0; $i < 10; $i++) {

            $data[] = [
                'agency_fee_amount' => '600000',
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
                'ad_type' => 'xxxxx',
            ];
        }
        exit(json_encode(['code' => 0, 'count' => 1000, 'data' => $data], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 代理费明细
     *
     * @return mixed
     */
    public function agency_fee_info()
    {
        $list = [];
        $this->assign('list', $list);

        return $this->fetch();
    }
}
