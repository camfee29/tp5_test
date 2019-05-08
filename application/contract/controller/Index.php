<?php

namespace app\contract\controller;

use think\Controller;
use think\Db;

class Index extends Controller
{
    private $receipt_type = [
        1 => '现金 ',
        2 => '冲抵'
    ];

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
            $where['c.contract_no'] = $contract_no;
        }
        $erp_contract_no = input('erp_contract_no');
        if (!empty($erp_contract_no)) {
            $where['c.erp_contract_no'] = $erp_contract_no;
        }
        $customer = input('customer');
        if (!empty($customer)) {
            $where['c.customer'] = $customer;
        }
        $agency = input('agency');
        if (!empty($agency)) {
            $where['c.agency'] = $agency;
        }
        $brand = input('brand');
        if (!empty($brand)) {
            $where['c.brand'] = $brand;
        }
        $direct_group = input('direct_group');
        if (!empty($direct_group)) {
            $where['cd.direct_group'] = $direct_group;
        }
        $direct_manager = input('direct_manager');
        if (!empty($direct_manager)) {
            $where['cd.direct_manager'] = $direct_manager;
        }
        $ad_type = input('ad_type');
        if (!empty($ad_type)) {
            $where['c.ad_type'] = $ad_type;
        }
        $channel = input('channel');
        if (!empty($channel)) {
            $where['c.channel'] = $channel;
        }
        $channel_manager = input('channel_manager');
        if (!empty($channel_manager)) {
            $where['c.channel_manager'] = $channel_manager;
        }
        $start_time = input('start_time');
        $end_time = input('end_time');
        if (!empty($start_time) && !empty($end_time)) {
            $where['c.launch_time'] = [['>=', strtotime($start_time)], ['<=', strtotime($end_time)]];
        } elseif (!empty($start_time)) {
            $where['c.launch_time'] = ['>=', strtotime($start_time)];
        } elseif (!empty($end_time)) {
            $where['c.launch_time'] = ['<=', strtotime($end_time)];
        }
        $count = Db::name('contract')->alias('c')->field('c.*')->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')->where($where)->count();
        $offset = ($page - 1) * $limit;
        $data = Db::name('contract')->alias('c')->field('c.*')->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')->where($where)->limit($offset, $limit)->select();
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
        $not_del = [];
        if (!empty($direct_update)) {
            foreach ($direct_update as $id => $val) {
                $not_del[$id] = $id;
                Db::name('contract_direct')->where('id', $id)->update($val);
            }
        }
        // 删除 必须在新增之前
        Db::name('contract_direct')->where(['contract_id' => $cid, 'id' => ['not in', $not_del]])->delete();
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
            if (!empty($_POST['expect'])) {
                $da = $_POST['expect'];
                $expect_add = $expect_update = [];
                foreach ($da['id'] as $k => $v) {
                    if (empty($v)) {
                        $expect_add[] = [
                            'contract_id' => $contract_id,
                            'expect_date' => trim($da['expect_date'][$k]),
                            'expect_amount' => floatval($da['expect_amount'][$k]),
                            'is_return' => intval($da['is_return'][$k]),
                            'add_time' => time(),
                        ];
                    } else {
                        $expect_update[$v] = [
                            'expect_date' => trim($da['expect_date'][$k]),
                            'expect_amount' => floatval($da['expect_amount'][$k]),
                            'is_return' => intval($da['is_return'][$k]),
                            'update_time' => time(),
                        ];
                    }
                }
                $not_del = [];
                if (!empty($expect_update)) {
                    foreach ($expect_update as $id => $val) {
                        $not_del[$id] = $id;
                        Db::name('contract_expect')->where('id', $id)->update($val);
                    }
                }
                // 删除 必须在新增之前
                Db::name('contract_expect')->where([
                    'contract_id' => $contract_id,
                    'id' => ['not in', $not_del]
                ])->delete();
                if (!empty($expect_add)) {
                    Db::name('contract_expect')->insertAll($expect_add);
                }
            }
            if (!empty($_POST['receipt'])) {
                $da = $_POST['receipt'];
                $receipt_add = $receipt_update = [];
                foreach ($da['id'] as $k => $v) {
                    if (empty($v)) {
                        $receipt_add[] = [
                            'contract_id' => $contract_id,
                            'expect_date' => trim($da['expect_date'][$k]),
                            'expect_amount' => floatval($da['expect_amount'][$k]),
                            'receipt_date' => trim($da['receipt_date'][$k]),
                            'receipt_amount' => floatval($da['receipt_amount'][$k]),
                            'receipt_type' => intval($da['receipt_type'][$k]),
                            'contract_no' => trim($da['contract_no'][$k]),
                            'is_return' => intval($da['is_return'][$k]),
                            'local_new' => trim($da['local_new'][$k]),
                            'local_new_amount' => floatval($da['local_new_amount'][$k]),
                            'local_fund' => floatval($da['local_fund'][$k]),
                            'local_fund_amount' => floatval($da['local_fund_amount'][$k]),
                            'local_special' => floatval($da['local_special'][$k]),
                            'local_special_amount' => floatval($da['local_special_amount'][$k]),
                            'agency_base' => floatval($da['agency_base'][$k]),
                            'agency_base_amount' => floatval($da['agency_base_amount'][$k]),
                            'agency_fund' => floatval($da['agency_fund'][$k]),
                            'agency_fund_amount' => floatval($da['agency_fund_amount'][$k]),
                            'agency_special' => floatval($da['agency_special'][$k]),
                            'agency_special_amount' => floatval($da['agency_special_amount'][$k]),
                            'agency_fee' => floatval($da['agency_fee'][$k]),
                            'agency_fee_amount' => floatval($da['agency_fee_amount'][$k]),
                            'add_time' => time(),
                        ];
                    } else {
                        $receipt_update[$v] = [
                            'expect_date' => trim($da['expect_date'][$k]),
                            'expect_amount' => floatval($da['expect_amount'][$k]),
                            'receipt_date' => trim($da['receipt_date'][$k]),
                            'receipt_amount' => floatval($da['receipt_amount'][$k]),
                            'receipt_type' => intval($da['receipt_type'][$k]),
                            'contract_no' => trim($da['contract_no'][$k]),
                            'is_return' => intval($da['is_return'][$k]),
                            'local_new' => trim($da['local_new'][$k]),
                            'local_new_amount' => floatval($da['local_new_amount'][$k]),
                            'local_fund' => floatval($da['local_fund'][$k]),
                            'local_fund_amount' => floatval($da['local_fund_amount'][$k]),
                            'local_special' => floatval($da['local_special'][$k]),
                            'local_special_amount' => floatval($da['local_special_amount'][$k]),
                            'agency_base' => floatval($da['agency_base'][$k]),
                            'agency_base_amount' => floatval($da['agency_base_amount'][$k]),
                            'agency_fund' => floatval($da['agency_fund'][$k]),
                            'agency_fund_amount' => floatval($da['agency_fund_amount'][$k]),
                            'agency_special' => floatval($da['agency_special'][$k]),
                            'agency_special_amount' => floatval($da['agency_special_amount'][$k]),
                            'agency_fee' => floatval($da['agency_fee'][$k]),
                            'agency_fee_amount' => floatval($da['agency_fee_amount'][$k]),
                            'update_time' => time(),
                        ];
                    }
                }
                $not_del = [];
                if (!empty($receipt_update)) {
                    foreach ($receipt_update as $id => $val) {
                        $not_del[$id] = $id;
                        Db::name('contract_receipt')->where('id', $id)->update($val);
                    }
                }
                // 删除 必须在新增之前
                Db::name('contract_receipt')->where([
                    'contract_id' => $contract_id,
                    'id' => ['not in', $not_del]
                ])->delete();
                if (!empty($receipt_add)) {
                    Db::name('contract_receipt')->insertAll($receipt_add);
                }
            }
            $this->success('操作成功');
        }
        $expect = Db::name('contract_expect')->where('contract_id', $contract_id)->select();
        $receipt = Db::name('contract_receipt')->where('contract_id', $contract_id)->select();

        $this->assign('contract_id', $contract_id);
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
        $param = $_GET;
        $this->assign('param', $param);

        return $this->fetch();
    }

    /**
     * 应收信息
     *
     * @return mixed
     */
    public function expect()
    {
        $param = $_GET;
        $this->assign('param', $param);

        return $this->fetch();
    }

    public function ajaxExpect()
    {
        $page = input('page', 1);
        $limit = input('limit', 10);
        $where = [];
        $contract_no = input('contract_no');
        if (!empty($contract_no)) {
            $where['c.contract_no'] = $contract_no;
        }
        $erp_contract_no = input('erp_contract_no');
        if (!empty($erp_contract_no)) {
            $where['c.erp_contract_no'] = $erp_contract_no;
        }
        $customer = input('customer');
        if (!empty($customer)) {
            $where['c.customer'] = $customer;
        }
        $agency = input('agency');
        if (!empty($agency)) {
            $where['c.agency'] = $agency;
        }
        $brand = input('brand');
        if (!empty($brand)) {
            $where['c.brand'] = $brand;
        }
        $direct_group = input('direct_group');
        if (!empty($direct_group)) {
            $where['cd.direct_group'] = $direct_group;
        }
        $direct_manager = input('direct_manager');
        if (!empty($direct_manager)) {
            $where['cd.direct_manager'] = $direct_manager;
        }
        $ad_type = input('ad_type');
        if (!empty($ad_type)) {
            $where['c.ad_type'] = $ad_type;
        }
        $channel = input('channel');
        if (!empty($channel)) {
            $where['c.channel'] = $channel;
        }
        $channel_manager = input('channel_manager');
        if (!empty($channel_manager)) {
            $where['c.channel_manager'] = $channel_manager;
        }
        $start_time = input('start_time');
        $end_time = input('end_time');
        if (!empty($start_time) && !empty($end_time)) {
            $where['c.launch_time'] = [['>=', strtotime($start_time)], ['<=', strtotime($end_time)]];
        } elseif (!empty($start_time)) {
            $where['c.launch_time'] = ['>=', strtotime($start_time)];
        } elseif (!empty($end_time)) {
            $where['c.launch_time'] = ['<=', strtotime($end_time)];
        }
        $count = Db::name('contract_expect')->alias('ce')->field('ce.*,c.*')->join('contract c', 'ce.contract_id=c.contract_id', 'LEFT')->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')->where($where)->count();
        $offset = ($page - 1) * $limit;
        $data = Db::name('contract_expect')->alias('ce')->field('ce.*,c.*')->join('contract c', 'ce.contract_id=c.contract_id', 'LEFT')->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')->where($where)->limit($offset, $limit)->select();
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
     * 到账信息
     *
     * @return mixed
     */
    public function receipt()
    {
        $param = $_GET;
        $this->assign('param', $param);

        return $this->fetch();
    }

    public function ajaxReceipt()
    {
        $page = input('page', 1);
        $limit = input('limit', 10);
        $where = [];
        $contract_no = input('contract_no');
        if (!empty($contract_no)) {
            $where['c.contract_no'] = $contract_no;
        }
        $erp_contract_no = input('erp_contract_no');
        if (!empty($erp_contract_no)) {
            $where['c.erp_contract_no'] = $erp_contract_no;
        }
        $customer = input('customer');
        if (!empty($customer)) {
            $where['c.customer'] = $customer;
        }
        $agency = input('agency');
        if (!empty($agency)) {
            $where['c.agency'] = $agency;
        }
        $brand = input('brand');
        if (!empty($brand)) {
            $where['c.brand'] = $brand;
        }
        $direct_group = input('direct_group');
        if (!empty($direct_group)) {
            $where['cd.direct_group'] = $direct_group;
        }
        $direct_manager = input('direct_manager');
        if (!empty($direct_manager)) {
            $where['cd.direct_manager'] = $direct_manager;
        }
        $ad_type = input('ad_type');
        if (!empty($ad_type)) {
            $where['c.ad_type'] = $ad_type;
        }
        $channel = input('channel');
        if (!empty($channel)) {
            $where['c.channel'] = $channel;
        }
        $channel_manager = input('channel_manager');
        if (!empty($channel_manager)) {
            $where['c.channel_manager'] = $channel_manager;
        }
        $start_time = input('start_time');
        $end_time = input('end_time');
        if (!empty($start_time) && !empty($end_time)) {
            $where['c.launch_time'] = [['>=', strtotime($start_time)], ['<=', strtotime($end_time)]];
        } elseif (!empty($start_time)) {
            $where['c.launch_time'] = ['>=', strtotime($start_time)];
        } elseif (!empty($end_time)) {
            $where['c.launch_time'] = ['<=', strtotime($end_time)];
        }
        $count = Db::name('contract_receipt')->alias('cr')->field('cr.*,cr.contract_no as ex_contract_no,c.*')->join('contract c', 'cr.contract_id=c.contract_id')->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')->where($where)->count();
        $offset = ($page - 1) * $limit;
        $data = Db::name('contract_receipt')->alias('cr')->field('cr.*,cr.contract_no as ex_contract_no,c.*')->join('contract c', 'cr.contract_id=c.contract_id')->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')->where($where)->limit($offset, $limit)->select();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['launch_time'] = date('Y-m-d', $v['launch_time']);
                $data[$k]['start_time'] = date('Y-m-d', $v['start_time']);
                $data[$k]['end_time'] = date('Y-m-d', $v['end_time']);
                $data[$k]['receipt_type'] = $this->receipt_type[$v['receipt_type']];
            }
        }
        exit(json_encode(['code' => 0, 'count' => $count, 'data' => $data]));
    }

    /**
     * 逾期信息
     *
     * @return mixed
     */
    public function overdue()
    {
        $param = $_GET;
        $this->assign('param', $param);

        return $this->fetch();
    }

    public function ajaxOverdue()
    {
        $page = input('page', 1);
        $limit = input('limit', 10);
        $where = [
            'ce.expect_date' => ['<', date('Y-m-d')],
            'cr.receipt_date' => [['exp', Db::raw('IS NULL')], ['>', 'cr.expect_date'], 'or'],
        ];
        $contract_no = input('contract_no');
        if (!empty($contract_no)) {
            $where['c.contract_no'] = $contract_no;
        }
        $erp_contract_no = input('erp_contract_no');
        if (!empty($erp_contract_no)) {
            $where['c.erp_contract_no'] = $erp_contract_no;
        }
        $customer = input('customer');
        if (!empty($customer)) {
            $where['c.customer'] = $customer;
        }
        $agency = input('agency');
        if (!empty($agency)) {
            $where['c.agency'] = $agency;
        }
        $brand = input('brand');
        if (!empty($brand)) {
            $where['c.brand'] = $brand;
        }
        $ad_type = input('ad_type');
        if (!empty($ad_type)) {
            $where['c.ad_type'] = $ad_type;
        }
        $channel = input('channel');
        if (!empty($channel)) {
            $where['c.channel'] = $channel;
        }
        $channel_manager = input('channel_manager');
        if (!empty($channel_manager)) {
            $where['c.channel_manager'] = $channel_manager;
        }
        $start_time = input('start_time');
        $end_time = input('end_time');
        if (!empty($start_time) && !empty($end_time)) {
            $where['c.launch_time'] = [['>=', strtotime($start_time)], ['<=', strtotime($end_time)]];
        } elseif (!empty($start_time)) {
            $where['c.launch_time'] = ['>=', strtotime($start_time)];
        } elseif (!empty($end_time)) {
            $where['c.launch_time'] = ['<=', strtotime($end_time)];
        }
        $count = Db::name('contract_expect')->alias('ce')->field('ce.id,ce.expect_date,ce.expect_amount,(
		CASE
		WHEN cr.receipt_amount IS NULL THEN
			0
		ELSE
			sum(cr.receipt_amount)
		END
	) AS receipt_amount,c.*')->join('contract_receipt cr', 'cr.contract_id = ce.contract_id and cr.expect_date = ce.expect_date', 'LEFT')->join('contract c', 'ce.contract_id=c.contract_id', 'LEFT')->where($where)->group('ce.expect_date')->count();
        $offset = ($page - 1) * $limit;
        $data = Db::name('contract_expect')->alias('ce')->field('ce.id,ce.expect_date,ce.expect_amount,(
		CASE
		WHEN cr.receipt_amount IS NULL THEN
			0
		ELSE
			sum(cr.receipt_amount)
		END
	) AS receipt_amount,c.*')->join('contract_receipt cr', 'ce.contract_id = cr.contract_id and ce.expect_date = cr.expect_date', 'LEFT')->join('contract c', 'ce.contract_id=c.contract_id', 'LEFT')->where($where)->group('ce.expect_date')->limit($offset, $limit)->select();
        if (!empty($data)) {
            $contract_ids = $contract_expect_dates = [];
            foreach ($data as $k => $v) {
                $contract_ids[$v['contract_id']] = $v['contract_id'];
                $contract_expect_dates[$v['expect_date']] = $v['expect_date'];
            }
            $receipts = Db::name('contract_receipt')->field('*,sum(receipt_amount) as receipt_amount,max(receipt_date) as receipt_date')->where([
                'contract_id' => [
                    'in',
                    $contract_ids
                ],
                'expect_date' => ['in', $contract_expect_dates]
            ])->group('contract_id,expect_date')->select();
            $receipt_list = [];
            if (!empty($receipts)) {
                foreach ($receipts as $k => $v) {
                    $receipt_list[$v['contract_id']][$v['expect_date']] = $v;
                }
            }
            foreach ($data as $k => $v) {
                $data[$k]['launch_time'] = date('Y-m-d', $v['launch_time']);
                $data[$k]['start_time'] = date('Y-m-d', $v['start_time']);
                $data[$k]['end_time'] = date('Y-m-d', $v['end_time']);
                $data[$k]['overdue_date'] = $v['expect_date'];
                if (empty($v['receipt_amount']) || $v['receipt_amount'] == 0) {
                    $data[$k]['overdue_amount'] = $v['expect_amount'];
                } else {
                    $data[$k]['overdue_amount'] = $v['receipt_amount'];
                }
                $data[$k]['overdue_day'] = floor((strtotime(date('Y-m-d')) - strtotime($v['expect_date'])) / 86400);
                if (isset($receipt_list[$v['contract_id']][$v['expect_date']])) {
                    $tmp = $receipt_list[$v['contract_id']][$v['expect_date']];
                    if ($tmp['receipt_amount'] >= $v['expect_amount']) {
                        $data[$k]['overdue_day'] = floor((strtotime($tmp['receipt_date']) - strtotime($v['expect_date'])) / 86400);
                    }
                }
            }
        }
        exit(json_encode(['code' => 0, 'count' => $count, 'data' => $data]));
    }

    /**
     * 结算余额
     *
     * @return mixed
     */
    public function balance()
    {
        $param = $_GET;
        $this->assign('param', $param);

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
        exit(json_encode(['code' => 0, 'count' => 1000, 'data' => $data]));
    }

    /**
     * 代理服务费
     *
     * @return mixed
     */
    public function agency_fee()
    {
        $param = $_GET;
        $this->assign('param', $param);

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
        exit(json_encode(['code' => 0, 'count' => 1000, 'data' => $data]));
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
