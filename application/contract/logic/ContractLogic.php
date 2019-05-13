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
     * @throws \think\Exception
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
}