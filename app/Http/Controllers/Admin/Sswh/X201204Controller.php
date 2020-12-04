<?php

namespace App\Http\Controllers\Admin\Sswh;

use App\Exports\X201204Export;
use App\Http\Controllers\Common\BaseV1Controller as Controller;
use App\Models\Sswh\X201204\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class X201204Controller extends Controller
{
    public function index(Request $request)
    {
        $nameOrCode = $request->input('nameOrPhone');
        $status = $request->input('status');
        $query = User::when(!preg_match("/^\d{11}$/", $nameOrCode), function ($query) use ($nameOrCode) {
            return $query->where('name', 'like', '%'.$nameOrCode.'%');
        })
            ->when(preg_match("/^\d{11}$/", $nameOrCode), function ($query) use ($nameOrCode) {
                return $query->where('phone', '=', $nameOrCode);
            })
            ->when($status === '1', function ($query) use ($status) {
                return $query->where('gender', '!=', 0);
            })
            ->when($status === '0', function ($query) use ($status) {
                return $query->where('gender',$status);
            });
        $paginator = $query->orderBy('updated_at', 'desc')->paginate(15);
        $exportUrl = asset('/vlvl/x201204/export');
        $total = User::count();
        $redis = app('redis');
        $redis->select(0);
        $redisShareData = $redis->hGetAll('wx:view:dq201204');
        return view('sswh.sswhAdmin.x201204', [
            'title' => '大桥·"蟹蟹"有礼', 'paginator' => $paginator, 'exportUrl' => $exportUrl,
            'redisShareData' => $redisShareData,
            'total' => $total
        ]);
    }

    public function export()
    {
        return Excel::download(new X201204Export(), '大桥·"蟹蟹"有礼报名名单.xlsx');
    }


}