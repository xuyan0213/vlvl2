<?php

namespace App\Models\Qwt\X191212;

use Illuminate\Database\Eloquent\Model;
use EasyWeChat\Factory;

class User extends Model
{
    //

    protected $table = 'x191212_user';

    protected $guarded = [];

    /*
     * 处理抽奖 -- 开始
     * #######################################################################################################################################################
     * */
    private $zhongJiangLv = 0;  //设置中奖率 如果大于 1 始终会转化为0~1之间的小数
    private $prizeKey = 'prize_id';  // 数据库里面的 中奖类型索引的字段 名称
    /*
     * 中奖配置数组
     *  v:出现的权重
     * prize_id 为数据表里面的中奖类型 ID值
     * prize_level_name 中奖类型 如：一等奖，二等奖。。。
     * prize_name 奖品或奖品物品名称
     *  v     奖品的权重
     * count  当前中奖数量
     * limit  预先准备的该奖品个数限制
     * */
    protected $prizeConf = [
        /*中奖数组*/
        'prize' => [
//            0 => ['prize_id' => 1, 'prize_level_name' => '一等奖', 'prize_name' => '任天堂Switch国行版', 'money' => 3, 'v' => 10, 'count' => 0, 'limit' => 0],
//            1 => ['prize_id' => 2, 'prize_level_name' => '二等奖', 'prize_name' => '小度在家1S智能音箱', 'money' => 2, 'v' => 20, 'count' => 0, 'limit' => 0],
//            2 => ['prize_id' => 3, 'prize_level_name' => '三等奖', 'prize_name' => '冰豹Kone pure Owl-Eye电竞鼠标', 'money' => 1, 'v' => 60, 'count' => 0, 'limit' => 0],
        ],
        /*不中奖   --未中奖*/
        'notPrize' => ['prize_id' => 0, 'prize_level_name' => '未中奖', 'prize_name' => '未中奖', 'money' => 0, 'v' => 100, 'count' => 0, 'limit' => 100000],
    ];

    /*
     * 随机抽奖  中奖几率始终不变
     * */
    public function fixRandomPrize($redisCountKey)
    {
        $prizeCountKey = $redisCountKey;
        $redis = app('redis');
        $redis->select(12);
        if (!$prizeCountArr = $redis->hGetAll($prizeCountKey)) {
            throw new \Exception('缓存获取中奖统计失败', -2);
        }
        $zhongjianglv = $this->parseZhongJiangLv($this->zhongJiangLv); //解析中奖率 防止出错
        //降低中奖率
//        if ($redis->get($redisCountKey . ':prizeNum') >= 15) {
//            $zhongjianglv = 0.07;       //奖品发放超过一半后
//        }
        $finalConf = []; //最终生成的配置数组
        $jingduSum = 0; //精度计数
        $resultPrize = null; // 最终的中奖的信息

        foreach ($this->prizeConf['prize'] as $k => $arr) {
            $prize_id = $this->prizeConf['prize'][$k]['prize_id'];
            $this->prizeConf['prize'][$k]['count'] = $prizeCountArr[$prize_id];
            $this->prizeConf['prize'][$k]['v'] = $arr['v'] * 1;   //中奖权重 增加除不中奖以外的权重
        }
        /*去除奖品发完的奖项*/
        foreach ($this->prizeConf['prize'] as $k => $arr) {
            if ($arr['count'] < $arr['limit']) {
                $finalConf[] = $arr;
                $jingduSum += $arr['v'];
            }
        }
        if (count($finalConf) > 0) {  //奖品还没发完
            $jingduSum = $jingduSum / $zhongjianglv;
            $this->prizeConf['notPrize']['v'] = round($jingduSum * (1 - $zhongjianglv));  //四舍五入
        } else {    // 奖品已发完
            $jingduSum = $this->prizeConf['notPrize']['v'] = 1000;
        }
        $finalConf[] = $this->prizeConf['notPrize'];
//        dd($jingduSum);
        /*计算百分比*/
        foreach ($finalConf as $k => $arr) {
            $finalConf[$k]['v100'] = round($arr['v'] * 100 / $jingduSum, 2) . '%';
        }
        /*随机抽奖*/
        foreach ($finalConf as $key => $prize) {
            $randNum = mt_rand(1, $jingduSum);
            if ($randNum <= $prize['v']) {
                $resultPrize = $prize;
                break;
            } else {
                $jingduSum -= $prize['v'];
            }
        }
        return ['resultPrize' => $resultPrize, 'finalConf' => $finalConf, 'prizeConf' => $this->prizeConf, 'prizeCountKey' => $prizeCountKey];
    }

    /*
     * 转换中奖率
     * */
    public function parseZhongJiangLv($numberic)
    {
        if (!is_numeric($numberic)) {
            throw new \Exception('中奖率参数不正确', -1);
        }
        while ($numberic > 1) {
            $numberic /= 10;
        }
        return $numberic * 1;
    }

    /*
     * 处理抽奖 -- 结束
     * #######################################################################################################################################################
     * */
    public function getZJLv()
    {
        $res = [];
        $res['hubei'] = $this->parseZhongJiangLv($this->zhongJiangLv['hubei']);
        $res['other'] = $this->parseZhongJiangLv($this->zhongJiangLv['other']);
//        $res['other'] = 0.3;
        //降低中奖率
//        if (!in_array(intval(date('H')), [10])) {
//            $res['hubei'] = 0.60;       //湖北高峰期以外的中奖率
//        }
        $res['hubei_str'] = round($res['hubei'] * 100, 2) . "%";
        $res['other_str'] = round($res['other'] * 100, 2) . "%";
        return $res;
    }
}