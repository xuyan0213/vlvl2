<?php

namespace App\Http\Controllers\Admin\Sswh;

use App\Exports\X200521Export;
use App\Http\Controllers\Common\BaseV1Controller as Controller;
use App\Models\Sswh\X200521\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class X200521Controller extends Controller
{
    public function index(Request $request)
    {
        $prize = $request->input('prize');
        $status = $request->input('status');
        $query = User::when($prize != '', function ($query) use ($prize) {
            return $query->where('money', $prize);
        })
        ->when($status != '', function ($query) use ($status) {
                return $query->where('status', $status);
            });
        $paginator = $query->orderBy('updated_at', 'desc')->paginate(15);
        $exportUrl = asset('/vlvl/x200521/export');
        $redis = app('redis');
        $redis->select(0);
        $redisShareData = $redis->hGetAll('wx:view:jq20200521');
        return view('sswh.sswhAdmin.x200521', [
            'title' => '中国中铁·答题领红包',
            'paginator' => $paginator,
            'exportUrl' => $exportUrl,
            'redisShareData' => $redisShareData,
        ]);
    }

    public function export()
    {
        return Excel::download(new X200521Export(), '中国中铁·答题领红包.xlsx');
    }
}
