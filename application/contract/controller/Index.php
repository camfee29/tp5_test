<?php

namespace app\contract\controller;

use think\Controller;
use think\Db;
use app\contract\logic\ContractLogic;

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
        $this->buildWhere($where);
        $count = Db::name('contract')->alias('c')
            ->field('c.*')
            ->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')
            ->group('c.contract_id')
            ->where($where)->count();
        $offset = ($page - 1) * $limit;
        $data = Db::name('contract')->alias('c')
            ->field('c.*')
            ->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')
            ->group('c.contract_id')
            ->where($where)->limit($offset, $limit)->select();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['launch_time'] = date('Y-m-d', $v['launch_time']);
                $data[$k]['start_time'] = date('Y-m-d', $v['start_time']);
                $data[$k]['end_time'] = date('Y-m-d', $v['end_time']);
                $data[$k]['is_erp'] = $v['is_erp'] ? '是' : '否';
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
        if ($data['amount'] > $data['final_amount']) {
            $data['balance_amount'] = $data['balance'] = $data['amount'] - $data['final_amount'];
        }
        if (!empty($cid)) {
            // 可用结算余额等计算
            $logic = new ContractLogic();
            $info = $data;
            $info['contract_id'] = $cid;
            $data['balance'] = $logic->calcBalance($info);
            $data['total_receipt_amount'] = $logic->calcReceipt($info);
            $data['mortgage_amount'] = $logic->calcMortgage($info);
            $data['charge_amount'] = $logic->calcCharge($info);
            $data['overdue_amount'] = $logic->calcOverdue($info);
            $data['agency_fee_amount'] = $logic->calcAgencyFee($info);
            $data['duty_amount'] = $logic->calcDuty($info);
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
                if (empty($direct_group[$k]) && empty($direct_manager[$k]) && empty($rate[$k])) {
                    continue;
                }
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
            // 应收信息修改
            if (!empty($_POST['expect'])) {
                $da = $_POST['expect'];
                $expect_add = $expect_update = [];
                foreach ($da['id'] as $k => $v) {
                    if (empty($da['expect_date'][$k]) && empty($da['expect_amount'][$k])) {
                        continue;
                    }
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
            // 到账信息修改
            if (!empty($_POST['receipt'])) {
                $logic = new ContractLogic();
                $da = $_POST['receipt'];
                $receipt_add = $receipt_update = [];
                foreach ($da['id'] as $k => $v) {
                    if (empty($da['expect_date'][$k]) && empty($da['receipt_date'][$k])) {
                        continue;
                    }
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
                $not_del = $contract_nos = [];
                $err_msg = '';
                if (!empty($receipt_update)) {
                    foreach ($receipt_update as $id => $val) {
                        $not_del[$id] = $id;
                        if ($val['receipt_type'] == 2 && !empty($val['contract_no'])) {
                            $contract_nos[$val['contract_no']] = $val['contract_no'];
                            $err_msg .= $logic->checkContractBalance($val, $id);
                        }
                        Db::name('contract_receipt')->where('id', $id)->update($val);
                    }
                }
                // 删除 必须在新增之前
                Db::name('contract_receipt')->where([
                    'contract_id' => $contract_id,
                    'id' => ['not in', $not_del]
                ])->delete();
                if (!empty($receipt_add)) {
                    foreach ($receipt_add as $val) {
                        if ($val['receipt_type'] == 2 && !empty($val['contract_no'])) {
                            $contract_nos[$val['contract_no']] = $val['contract_no'];
                            $err_msg .= $logic->checkContractBalance($val);
                        }
                        Db::name('contract_receipt')->insert($val);
                    }
                }
                $info = [
                    'contract_id' => $contract_id,
                ];
                $logic->calcReceipt($info, true);
                $logic->calcCharge($info, true);
                $logic->calcOverdue($info, true);
                $logic->calcAgencyFee($info, true);
                if (!empty($contract_nos)) {
                    foreach ($contract_nos as $contract_no) {
                        $logic->calcMortgage(['contract_no' => $contract_no], true);
                    }
                }
                if (!empty($err_msg)) {
                    $this->error($err_msg);
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
     * 检查获取应收信息
     */
    public function ajaxCheckExpectDate()
    {
        $id = input('id', 0, 'intval');
        $date = input('date', '', 'trim');
        $data = [];
        if (!empty($id) && !empty($date)) {
            $where = [
                'ce.contract_id' => $id,
                'ce.expect_date' => $date
            ];
            $data = Db::name('contract_expect')->alias('ce')
                ->field('ce.*,c.*')
                ->join('contract c', 'c.contract_id=ce.contract_id', 'LEFT')
                ->where($where)
                ->find();
        }

        $this->ajaxReturn($data);
    }

    /**
     * 数据统计
     * @return mixed
     * @throws \think\Exception
     */
    public function stat()
    {
        $param = $_GET;
        if (empty($param['start_time'])) {
            $param['start_time'] = date('Y-m-d', strtotime('-30 day'));
        }
        if (empty($param['end_time'])) {
            $param['end_time'] = date('Y-m-d');
        }
        $where = [];
        $this->buildWhere($where);

        $info = [
            'date_range' => $param['start_time'] . ' - ' . $param['end_time'],
        ];
        // 结算统计
        $where1 = $where;
        $where1['final_amount'] = ['>', 0];
        $info['balance_count'] = Db::name('contract')->alias('c')->where($where1)->count();
        $info['balance_amount'] = Db::name('contract')->alias('c')->where($where1)->sum('balance_amount');
        unset($where['c.launch_time']);
        // 应收统计
        $where2 = $where;
        $where2['ce.expect_date'] = [['>=', $param['start_time']], ['<=', $param['end_time']]];
        $info['expect_count'] = Db::name('contract_expect')->alias('ce')->join('contract c', 'c.contract_id=ce.contract_id', 'LEFT')->where($where2)->count();
        $info['expect_amount'] = Db::name('contract_expect')->alias('ce')->join('contract c', 'c.contract_id=ce.contract_id', 'LEFT')->where($where2)->sum('expect_amount');
        // 逾期统计
        $where2_1 = $where2_2 = $where2;
        $where2_2['cr.receipt_date'] = ['exp', Db::raw('<= cr.expect_date')];
        $overdue_list = Db::name('contract_expect')->alias('ce')
            ->field('ce.id,ce.expect_amount,(
		CASE
		WHEN cr.receipt_amount IS NULL THEN
			0
		ELSE
			sum(cr.receipt_amount)
		END
	) AS receipt_amount')
            ->join('contract_receipt cr', 'cr.contract_id = ce.contract_id and cr.expect_date = ce.expect_date', 'LEFT')
            ->join('contract c', 'ce.contract_id=c.contract_id', 'LEFT')
            ->where($where2_2)
            ->group('ce.contract_id,ce.expect_date')
            ->having('ce.expect_amount > receipt_amount')
            ->select();
        if (!empty($overdue_list)) {
            $ids = [];
            foreach ($overdue_list as $v) {
                $ids[$v['id']] = $v['id'];
            }
            $where_or = ['ce.id' => ['in',$ids]];
        }
        $where2_1['cr.receipt_date'] = [['exp', Db::raw('IS NULL')], ['exp', Db::raw('> cr.expect_date')], 'or'];
        $query = Db::name('contract_expect')->alias('ce')
            ->join('contract_receipt cr', 'cr.contract_id = ce.contract_id and cr.expect_date = ce.expect_date', 'LEFT')
            ->join('contract c', 'ce.contract_id=c.contract_id', 'LEFT')
            ->where($where2_1)
            ->group('ce.contract_id,ce.expect_date');
        if (!empty($where_or)) {
            $query->whereOr($where_or);
        }
        $info['overdue_count'] = $query->count();
        $info['overdue_amount'] = Db::name('contract_expect')->alias('ce')
            ->join('contract c', 'ce.contract_id=c.contract_id', 'LEFT')
            ->where($where2)
            ->sum('ce.expect_amount');
        $where2_3 = $where;
        $where2_3['cr.expect_date'] = [['>=', $param['start_time']], ['<=', $param['end_time']]];
        $where2_3['cr.receipt_date'] = ['exp', Db::raw('<= cr.expect_date')];
        $expect_amount = Db::name('contract_receipt')->alias('cr')
            ->join('contract c', 'cr.contract_id=c.contract_id', 'LEFT')
            ->where($where2_3)
            ->sum('cr.receipt_amount');
        $info['overdue_amount'] -= $expect_amount;
        // 到账统计
        $where3 = $where;
        $where3['cr.receipt_date'] = [['>=', $param['start_time']], ['<=', $param['end_time']]];
        $info['receipt_count'] = Db::name('contract_receipt')->alias('cr')->join('contract c', 'c.contract_id=cr.contract_id', 'LEFT')->where($where3)->count();
        $info['receipt_amount'] = Db::name('contract_receipt')->alias('cr')->join('contract c', 'c.contract_id=cr.contract_id', 'LEFT')->where($where3)->sum('receipt_amount');
        // 代理服务费统计
        $where3['cr.agency_fee'] = ['>', 0];
        $info['agency_fee_count'] = Db::name('contract_receipt')->alias('cr')->join('contract c', 'c.contract_id=cr.contract_id', 'LEFT')->where($where3)->count();
        $info['agency_fee_amount'] = Db::name('contract_receipt')->alias('cr')->join('contract c', 'c.contract_id=cr.contract_id', 'LEFT')->where($where3)->sum('cr.agency_fee_amount');

        $this->assign('param', $param);
        $this->assign('info', $info);

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
        $this->buildWhere($where);
        $count = Db::name('contract_expect')->alias('ce')
            ->field('ce.id,ce.expect_date,ce.expect_amount,ce.is_return,c.*')
            ->join('contract c', 'ce.contract_id=c.contract_id', 'LEFT')
            ->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')
            ->group('ce.contract_id,ce.expect_date')
            ->where($where)->count();
        $offset = ($page - 1) * $limit;
        $data = Db::name('contract_expect')->alias('ce')
            ->field('ce.id,ce.expect_date,ce.expect_amount,ce.is_return,c.*')
            ->join('contract c', 'ce.contract_id=c.contract_id', 'LEFT')
            ->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')
            ->group('ce.contract_id,ce.expect_date')
            ->where($where)->limit($offset, $limit)->select();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['launch_time'] = date('Y-m-d', $v['launch_time']);
                $data[$k]['start_time'] = date('Y-m-d', $v['start_time']);
                $data[$k]['end_time'] = date('Y-m-d', $v['end_time']);
            }
        }

        $this->ajaxReturn(['code' => 0, 'count' => $count, 'data' => $data]);
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
        $this->buildWhere($where);
        $count = Db::name('contract_receipt')->alias('cr')
            ->field('cr.id,cr.receipt_date,cr.receipt_amount,cr.receipt_type,cr.expect_date,cr.expect_amount,cr.is_return,cr.contract_no as ex_contract_no,c.*')
            ->join('contract c', 'cr.contract_id=c.contract_id')
            ->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')
            ->group('cr.contract_id,cr.receipt_date,cr.receipt_date')
            ->where($where)->count();
        $offset = ($page - 1) * $limit;
        $data = Db::name('contract_receipt')->alias('cr')
            ->field('cr.*,cr.contract_no as ex_contract_no,c.*')
            ->join('contract c', 'cr.contract_id=c.contract_id')
            ->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')
            ->group('cr.contract_id,cr.receipt_date,cr.receipt_date')
            ->where($where)->limit($offset, $limit)->select();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['launch_time'] = date('Y-m-d', $v['launch_time']);
                $data[$k]['start_time'] = date('Y-m-d', $v['start_time']);
                $data[$k]['end_time'] = date('Y-m-d', $v['end_time']);
                $data[$k]['receipt_type'] = $this->receipt_type[$v['receipt_type']];
            }
        }

        $this->ajaxReturn(['code' => 0, 'count' => $count, 'data' => $data]);
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
            'cr.receipt_date' => ['exp', Db::raw('<= cr.expect_date')],
        ];
        $this->buildWhere($where);
        $list = Db::name('contract_expect')->alias('ce')
            ->field('ce.id,ce.expect_amount,(
		CASE
		WHEN cr.receipt_amount IS NULL THEN
			0
		ELSE
			sum(cr.receipt_amount)
		END
	) AS receipt_amount')
            ->join('contract_receipt cr', 'cr.contract_id = ce.contract_id and cr.expect_date = ce.expect_date', 'LEFT')
            ->join('contract c', 'ce.contract_id=c.contract_id', 'LEFT')
            ->where($where)
            ->group('ce.contract_id,ce.expect_date')
            ->having('ce.expect_amount > receipt_amount')
            ->order('ce.id', 'desc')
            ->select();
        $ids = [];
        if (!empty($list)) {
            foreach ($list as $v) {
                $ids[$v['id']] = $v['id'];
            }
            unset($list);
        }
        $where = [
            'ce.expect_date' => ['<', date('Y-m-d')],
            'cr.receipt_date' => [['exp', Db::raw('IS NULL')], ['exp', Db::raw('> cr.expect_date')], 'or'],
        ];
        if (!empty($ids)) {
            $where_or = ['ce.id' => ['in', $ids]];
        }
        $this->buildWhere($where);
        $query = Db::name('contract_expect')->alias('ce')
            ->field('ce.id,ce.expect_date,ce.expect_amount,(
		CASE
		WHEN cr.receipt_amount IS NULL THEN
			0
		ELSE
			sum(cr.receipt_amount)
		END
	) AS receipt_amount,c.*')
            ->join('contract_receipt cr', 'cr.contract_id = ce.contract_id and cr.expect_date = ce.expect_date', 'LEFT')
            ->join('contract c', 'ce.contract_id=c.contract_id', 'LEFT')
            ->where($where)
            ->group('ce.contract_id,ce.expect_date');
        if (!empty($where_or)) {
            $query = $query->whereOr($where_or);
        }
        $count = $query->count();
        $offset = ($page - 1) * $limit;
        $query = Db::name('contract_expect')->alias('ce')
            ->field('ce.id,ce.expect_date,ce.expect_amount,(
		CASE
		WHEN cr.receipt_amount IS NULL THEN
			0
		ELSE
			sum(cr.receipt_amount)
		END
	) AS receipt_amount,c.*')
            ->join('contract_receipt cr', 'ce.contract_id = cr.contract_id and ce.expect_date = cr.expect_date', 'LEFT')
            ->join('contract c', 'ce.contract_id=c.contract_id', 'LEFT')
            ->where($where)
            ->group('ce.contract_id,ce.expect_date')
            ->order('ce.id', 'desc')
            ->limit($offset, $limit);
        if (!empty($where_or)) {
            $query = $query->whereOr($where_or);
        }
        $data = $query->select();
        if (!empty($data)) {
            $contract_ids = $contract_expect_dates = [];
            foreach ($data as $k => $v) {
                $contract_ids[$v['contract_id']] = $v['contract_id'];
                $contract_expect_dates[$v['expect_date']] = $v['expect_date'];
            }
            // 计算逾期天数
            $receipts = Db::name('contract_receipt')
                ->field('*,sum(receipt_amount) as receipt_amount,max(receipt_date) as receipt_date')
                ->where([
                'contract_id' => ['in', $contract_ids],
                'expect_date' => ['in', $contract_expect_dates]
            ])
                ->group('contract_id,expect_date')
                ->select();
            $receipt_list = [];
            if (!empty($receipts)) {
                foreach ($receipts as $k => $v) {
                    $receipt_list[$v['contract_id']][$v['expect_date']] = $v;
                }
            }
            // 计算逾期金额
            $receipts = Db::name('contract_receipt')
                ->field('*,sum(receipt_amount) as receipt_amount')
                ->where([
                'contract_id' => ['in', $contract_ids],
                'expect_date' => [['in', $contract_expect_dates],['exp', Db::raw('>= receipt_date')]]
            ])
                ->group('contract_id,expect_date')->select();
            $receipt_amount = [];
            if (!empty($receipts)) {
                foreach ($receipts as $k => $v) {
                    $receipt_amount[$v['contract_id']][$v['expect_date']] = $v;
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
                    if (isset($receipt_amount[$v['contract_id']][$v['expect_date']])) {
                        $tmp = $receipt_amount[$v['contract_id']][$v['expect_date']];
                        $data[$k]['overdue_amount'] = $v['expect_amount'] - $tmp['receipt_amount'];
                    } else {
                        $data[$k]['overdue_amount'] = $v['expect_amount'];
                    }
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

        $this->ajaxReturn(['code' => 0, 'count' => $count, 'data' => $data]);
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
        $where = [];
        $this->buildWhere($where);
        $count = Db::name('contract')->alias('c')
            ->field('c.*')
            ->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')
            ->group('c.contract_id')
            ->where($where)->count();
        $offset = ($page - 1) * $limit;
        $data = Db::name('contract')->alias('c')
            ->field('c.*')
            ->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')
            ->group('c.contract_id')
            ->where($where)->limit($offset, $limit)->select();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['launch_time'] = date('Y-m-d', $v['launch_time']);
                $data[$k]['start_time'] = date('Y-m-d', $v['start_time']);
                $data[$k]['end_time'] = date('Y-m-d', $v['end_time']);
            }
        }

        $this->ajaxReturn(['code' => 0, 'count' => $count, 'data' => $data]);
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
        $where = [];
        $this->buildWhere($where);
        $count = Db::name('contract_receipt')->alias('cr')
            ->field('sum(cr.agency_fee_amount) as total_agency_fee_amount,c.*')
            ->join('contract c', 'cr.contract_id=c.contract_id')
            ->group('cr.contract_id')
            ->where($where)->count();
        $offset = ($page - 1) * $limit;
        $data = Db::name('contract_receipt')->alias('cr')
            ->field('sum(cr.agency_fee_amount) as total_agency_fee_amount,c.*')
            ->join('contract c', 'cr.contract_id=c.contract_id')
            ->group('cr.contract_id')
            ->where($where)->limit($offset, $limit)->select();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['launch_time'] = date('Y-m-d', $v['launch_time']);
                $data[$k]['start_time'] = date('Y-m-d', $v['start_time']);
                $data[$k]['end_time'] = date('Y-m-d', $v['end_time']);
            }
        }

        $this->ajaxReturn(['code' => 0, 'count' => $count, 'data' => $data]);
    }

    /**
     * 代理费明细
     *
     * @return mixed
     */
    public function agency_fee_info()
    {
        $cid = input('id', 0, 'intval');
        $where = [
            'cr.contract_id' => $cid,
        ];
        $data = Db::name('contract_receipt')->alias('cr')
            ->field('cr.*,cr.contract_no as ex_contract_no')
            ->join('contract c', 'cr.contract_id=c.contract_id')
            ->join('contract_direct cd', 'cd.contract_id=c.contract_id', 'LEFT')
            ->group('cr.contract_id,cr.receipt_date,cr.receipt_date')
            ->where($where)->select();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['is_return'] = $v['is_return'] ? '是' : '否';
            }
        }
        $this->assign('data', $data);

        return $this->fetch();
    }

    /**
     * 合同权责
     *
     * @return mixed
     */
    public function duty()
    {
        $contract_id = input('contract_id', 0, 'intval');
        if (!empty($_POST)) {
            $id = input('id', 0, 'intval');
            $da = $_POST;
            $data = [];
            $data['contract_id'] = $contract_id;
            $data['duty_amount'] = floatval($da['duty_amount']);
            $ai = $amount = 0;
            $last = floatval($da['duty12']);
            $avg = true;
            for ($i = 12; $i > 0; $i--) {
                $t = floatval($da['duty' . $i]);
                if ($avg && $t == $last) {
                    $ai = $i;
                } else {
                    $avg = false;
                    $amount += $t;
                }
                $data['duty' . $i] = $t;
            }
            if ($amount > $data['duty_amount']) {
                $this->error('分期权责大于权责总额');
            }
            $avg_amount = round(($data['duty_amount'] - $amount) / (12 - $ai + 1), 2);
            if ($avg_amount > 0) {
                for ($i = 12; $i >= $ai; $i--) {
                    $data['duty' . $i] = $avg_amount;
                }
            }
            if ($id) {
                $data['update_time'] = time();
                Db::name('contract_duty')->where('id', $id)->update($data);
            } else {
                $data['add_time'] = time();
                Db::name('contract_duty')->insert($data);
            }
            $logic = new ContractLogic();
            $info = [
                'contract_id' => $contract_id,
                'duty_amount' => -1,
            ];
            $logic->calcDuty($info, true);

            $this->success('操作成功');
        }
        $duty = Db::name('contract_duty')->where('contract_id', $contract_id)->find();
        $this->assign('contract_id', $contract_id);
        $this->assign('duty', $duty);

        return $this->fetch();
    }

    /**
     * 构建查询条件
     *
     * @param $where
     */
    private function buildWhere(&$where)
    {
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
    }

    /**
     *
     * @param $data
     */
    private function ajaxReturn($data) {
        exit(json_encode($data));
    }
}
