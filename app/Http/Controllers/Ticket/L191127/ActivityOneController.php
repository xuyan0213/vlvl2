<?php


namespace App\Http\Controllers\Ticket\L191127;

use Illuminate\Http\Request;
use App\Models\Ticket\L191127\User as User;
use App\Models\Ticket\L191127\ActivityOne;
use App\Models\Ticket\L191127\Admin;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Tdr\X191127RedpackExport;


class ActivityOneController extends Controller
{
    const ACT_1 = ['2019-12-10 00:00:00', '2019-12-31 23:59:59'];
    const ACT_2 = ['2019-12-10 00:00:00', '2020-01-25 23:59:59'];

    public function index(Request $request)
    {
        $where = function ($query) use ($request) {
            if ($request->filled('user_id')) {
                $users = User::select('id', 'nickname')
                    ->where('id', $request->user_id)
                    ->orWhere('nickname', 'like', '%' . $request->user_id . '%')
                    ->get()->toArray();
//                var_dump($users);exit;
                $query->whereIn('user_id', array_column($users, 'id'));
                $query->orWhereIn('id', [$request->user_id]);
            }
            if ($request->filled('status')) {
                if ($request->status == 11) {
                    $query->whereIn('status', [11, 21, 22]);
                } else {
                    $query->where('status', $request->status);
                }
            } else {
                $query->whereIn('status', [0, 10, 11, 20, 21, 22, 23]);
            }

            if ($request->filled('money')) {
                $query->where('money', $request->money);
            }
//            if ($request->filled('daterangepicker_checked_at')) {
//                $checkedArr = explode(' 至 ', $request->daterangepicker_checked_at);
//                $query->whereBetween('checked_at', [$checkedArr[0] . ' 00:00:00', $checkedArr[1] . ' 23:59:59']);
//            }
//            if ($request->filled('daterangepicker_created_at')) {
//                $createdArr = explode(' 至 ', $request->daterangepicker_created_at);
//                $query->whereBetween('created_at', [$createdArr[0] . ' 00:00:00', $createdArr[1] . ' 23:59:59']);
//            }
        };
        $paginator = ActivityOne::where($where)
//            ->whereHas('user', function ($query) {
//                $query->whereIn('status', [1, 2]);
//            })
            ->orderBy('id', 'desc')
            ->paginate(15);
        $moneys = ActivityOne::groupBy('money')->get('money')->toArray();
        $moneys = array_column($moneys, 'money');
        $act1MoneyCount['before'] = ActivityOne::sum('money');
        $act1MoneyCount['after'] = ActivityOne::sum('red_money');
        return view('ticket.' . Admin::$itemPrefix . '.act1.index', ['paginator'=>$paginator, 'act1MoneyCount'=>$act1MoneyCount,'moneys'=>$moneys]);
    }

    public function exportAct1(Request $request)
    {
        $rangeStr = '截止至' . date('Y-m-d');
        $where = function ($query) use ($request, & $rangeStr) {
            if ($request->filled('daterangepicker_created_at')) {
                $createdArr = explode(' 至 ', $request->daterangepicker_created_at);
                $query->whereBetween('created_at', [$createdArr[0], $createdArr[1]]);
                $rangeStr = substr($createdArr[0], 0, 10) . '至' . substr($createdArr[1], 0, 10);
            }
        };
        return (new X191127RedpackExport)->forWhere($where)->download('汤达人元气奖金池名单.xlsx');
    }
}