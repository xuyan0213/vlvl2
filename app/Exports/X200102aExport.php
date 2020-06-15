<?php

namespace App\Exports;

use App\Models\Sswh\X200102\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class X200102aExport implements FromCollection, WithStrictNullComparison,WithHeadings,ShouldAutoSize
{

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $users = User::get([ 'nickname', 'name', 'phone','address','prize','prized_at', 'updated_at','openid']);
        return $users;
    }
    // 定义 'headings()' 方法
    public function headings(): array
    {
        return [
            '昵称',
            '姓名',
            '电话',
            '地址',
            '奖品',
            '中奖时间',
            '参与时间',
            'openid'
        ];
    }
}