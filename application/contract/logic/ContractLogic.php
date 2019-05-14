<?php

namespace app\contract\logic;

use think\Model;
use think\Db;

/**
 * 合同逻辑定义
 * Class ContractLogic
 */
class ContractLogic extends Model
{
    /**
     * 合同可用余额计算
     *
     * @param $info
     * @return int
     */
    public function calcBalance($info)
    {
        $balance = 0;
        if (!empty($info['balance_amount'])) {
            $use_amount = Db::name('contract_receipt')->where([
                'receipt_type' => 2,
                'contract_no' => $info['contract_no']
            ])->sum('receipt_amount');
            $balance = $info['balance_amount'] - (float)$use_amount;
            $balance = max(0, $balance);
        }

        return $balance;
    }

    /**
     * 检查合同可用余额
     *
     * @param     $info
     * @param int $id
     * @return string
     */
    public function checkContractBalance(&$info, $id = 0)
    {
        $msg = '';
        $contract = Db::name('contract')->where('contract_no', $info['contract_no'])->find();
        if (empty($contract)) {
            $info['receipt_amount'] = 0;
            $msg = "冲抵合同{$info['contract_no']}不存在\r\n";
        } else {
            if (empty($id)) {
                if ($contract['balance'] < $info['receipt_amount']) {
                    $info['receipt_amount'] = $contract['balance'];
                    $msg = "冲抵合同{$info['contract_no']}可用结算余额不足\r\n";
                }
            } else {
                $old = Db::name('contract_receipt')->where('id', $id)->find();
                if (!empty($old)) {
                    if ($old['receipt_type'] == 2) {
                        $dec = $info['receipt_amount'] - $old['receipt_amount'];
                        if ($contract['balance'] < $dec) {
                            $dec = $contract['balance'];
                            $info['receipt_amount'] = $old['receipt_amount'] + $contract['balance'];
                            $msg = "冲抵合同{$info['contract_no']}可用结算余额不足\r\n";
                        }
                    } else {
                        if ($contract['balance'] < $info['receipt_amount']) {
                            $info['receipt_amount'] = $contract['balance'];
                            $msg = "冲抵合同{$info['contract_no']}可用结算余额不足\r\n";
                        }
                    }
                }

            }
        }
        if (empty($dec)) {
            $dec = $info['receipt_amount'];
        }
        if ($dec > 0) {
            Db::name('contract')->where('contract_no', $info['contract_no'])->setDec('balance', $dec);
        } elseif ($dec < 0) {
            Db::name('contract')->where('contract_no', $info['contract_no'])->setInc('balance', abs($dec));
        }

        return $msg;
    }

    /**
     * 合同到账更新计算
     *
     * @param      $info
     * @param bool $auto_update
     * @return float|int
     */
    public function calcReceipt($info, $auto_update = false)
    {
        $amount = Db::name('contract_receipt')->where('contract_id', $info['contract_id'])->sum('receipt_amount');
        if ($auto_update) {
            Db::name('contract')->where('contract_id', $info['contract_id'])->update(['total_receipt_amount' => $amount]);
        }

        return $amount;
    }

    /**
     * 合同冲抵其他合同总额计算
     *
     * @param      $info
     * @param bool $auto_update
     * @return float|int
     */
    public function calcMortgage($info, $auto_update = false)
    {
        $amount = Db::name('contract_receipt')->where([
            'receipt_type' => 2,
            'contract_no' => $info['contract_no']
        ])->sum('receipt_amount');
        if ($auto_update) {
            Db::name('contract')->where('contract_no', $info['contract_no'])->update(['mortgage_amount' => $amount]);
        }

        return $amount;
    }

    /**
     * 其他合同余额冲抵总额计算
     *
     * @param      $info
     * @param bool $auto_update
     * @return float|int
     */
    public function calcCharge($info, $auto_update = false)
    {
        $amount = Db::name('contract_receipt')->where([
            'receipt_type' => 2,
            'contract_id' => $info['contract_id']
        ])->sum('receipt_amount');
        if ($auto_update) {
            Db::name('contract')->where('contract_id', $info['contract_id'])->update(['charge_amount' => $amount]);
        }

        return $amount;
    }

    /**
     * 合同逾期总额计算
     *
     * @param      $info
     * @param bool $auto_update
     * @return float|int
     */
    public function calcOverdue($info, $auto_update = false)
    {
        $amount1 = Db::name('contract_expect')->where([
            'contract_id' => $info['contract_id'],
            'expect_date' => ['<', date('Y-m-d')]
        ])->sum('expect_amount');
        $amount2 = Db::name('contract_receipt')->where([
            'expect_date' => ['<', date('Y-m-d')],
            'receipt_date' => ['exp', Db::raw('<= expect_date')],
            'contract_id' => $info['contract_id']
        ])->sum('receipt_amount');
        $amount = $amount1 - $amount2;
        if ($auto_update) {
            Db::name('contract')->where('contract_id', $info['contract_id'])->update(['overdue_amount' => $amount]);
        }

        return $amount;
    }

    /**
     * 合同累计代理服务费计算
     *
     * @param      $info
     * @param bool $auto_update
     * @return float|int
     */
    public function calcAgencyFee($info, $auto_update = false)
    {
        $amount = Db::name('contract_receipt')->where([
            'contract_id' => $info['contract_id']
        ])->sum('agency_fee_amount');
        if ($auto_update) {
            Db::name('contract')->where('contract_id', $info['contract_id'])->update(['agency_fee_amount' => $amount]);
        }

        return $amount;
    }

    /**
     * 合同累计权责计算
     *
     * @param      $info
     * @param bool $auto_update
     * @return float|int
     */
    public function calcDuty($info, $auto_update = false)
    {
        $amount = Db::name('contract_duty')->where([
            'contract_id' => $info['contract_id']
        ])->value('duty_amount');
        if ($auto_update) {
            Db::name('contract')->where('contract_id', $info['contract_id'])->update(['duty_amount' => $amount]);
        }

        return $amount;
    }
}