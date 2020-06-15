<?php


namespace App\Models\Sswh\X191014;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'x191014_user';

    protected $guarded = [];

    /*
     * 生成唯一随机码
     * */
    public static function getUniqueCode($len)
    {
        $randomCode = self::getRandomCode($len);
        if (!self::where('prize_code', $randomCode)->first()) {
            return $randomCode;
        }
        return self::getUniqueCode($len);
    }

    /*
     * 生成随机码
     * */
    public static function getRandomCode($len)
    {
        $str = '123456789';
        $res = '';
        $strLen = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $res .= substr($str, mt_rand(0, $strLen - 1), 1);
        }
        return $res;
    }

    /*
    * 处理抽奖 -- 开始
    * #######################################################################################################################################################
    * */
    private $date = '20191020';
    private $zhongJiangLv = 1;
//    private $zhongJiangLv = [
//        'yuelu' => 80,
//        'other' => 25,
//    ];  //设置中奖率 如果大于 1 始终会转化为0~1之间的小数
    private $prizeKey = 'prize';  // 数据库里面的 中奖类型索引的字段 名称
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
            0 => ['prize_id' => 1, 'prize_level_name' => '一等奖', 'prize_name' => '100元代金券', 'money' => 1, 'v' => 30, 'count' => 0, 'limit' => 0],
            1 => ['prize_id' => 2, 'prize_level_name' => '二等奖', 'prize_name' => '50元代金券', 'money' => 2, 'v' => 30, 'count' => 0, 'limit' => 0],
            2 => ['prize_id' => 3, 'prize_level_name' => '三等奖', 'prize_name' => '10元代金券', 'money' => 3, 'v' => 40, 'count' => 0, 'limit' => 0],
        ],
        /*不中奖   --未中奖*/
        'notPirze' => ['prize_id' => 0, 'prize_level_name' => '未中奖', 'prize_name' => '未中奖', 'money' => 0, 'v' => 100, 'count' => 0, 'limit' => 100000],
    ];

    protected $prizeConfLimit = [
        /*测试*/
        'test' => [
            '0' => ['money' => 0, 'limit' => 100000],
            '1' => ['money' => 1, 'limit' => 100],
            '2' => ['money' => 2, 'limit' => 100],
            '3' => ['money' => 3, 'limit' => 200],
        ],
        /*第一天*/
        '20191019' => [
            '0' => ['money' => 0, 'limit' => 100000],
            '1' => ['money' => 1, 'limit' => 100],
            '2' => ['money' => 2, 'limit' => 100],
            '3' => ['money' => 3, 'limit' => 100]
        ],

    ];

    /*
     * 随机抽奖  中奖几率始终不变
     * */
    public function fixRandomPrize($redisCountKey, $dateStr)
    {
        if (!in_array($dateStr, ['20191019','20191020'])) {
            $dateStr = 'test';
        } else {
            $dateStr = '20191019';
        }
//        $region = null;
//        if (430104 == $address_code) {  //岳麓区
//            $region = 'yuelu';
//        } else {   //长沙除岳麓以外
//            $region = 'other';
//        }
//      $zhongjianglv = $this->parseZhongJiangLv($this->zhongJiangLv[$region]); //解析中奖率 防止出错
        $zhongjianglv = $this->parseZhongJiangLv($this->zhongJiangLv); //解析中奖率 防止出错
        $quanZhong = 100;
        $finalConf = []; //最终生成的配置数组
        $jingduSum = 0; //精度计数
        $resultPrize = null; // 最终的中奖的信息
//        $prizeCountKey = $redisCountKey . ':' . $region . ':' . $dateStr;
        $prizeCountKey = $redisCountKey . ':' . $dateStr;
        $redis = app('redis');
        $redis->select(12);
        if (!$prizeCountArr = $redis->hGetAll($prizeCountKey)) {
//            dd($prizeCountKey);
            throw new \Exception('缓存获取中奖统计失败', -2);
        }
        //配置
        foreach ($this->prizeConf['prize'] as $k => $arr) {
            $moneyStr = strval($arr['money']);
            $this->prizeConf['prize'][$k]['count'] = $prizeCountArr[$moneyStr];
//            $this->prizeConf['prize'][$k]['limit'] = $this->prizeConfLimit[$region][$dateStr][$moneyStr]['limit'];
            $this->prizeConf['prize'][$k]['limit'] = $this->prizeConfLimit[$dateStr][$moneyStr]['limit'];
            $this->prizeConf['prize'][$k]['v'] = $arr['v'] * $quanZhong;   //中奖权重 增加除不中奖以外的权重
        }
        /*去除奖品发完的奖项*/
        foreach ($this->prizeConf['prize'] as $k => $arr) {
            if ($arr['count'] < $arr['limit']) {
                $finalConf[] = $arr;
                $jingduSum += $arr['v'];
            }
        }
        if (count($finalConf) > 0) {  //奖品还没发完
            $jingduSum = ceil($jingduSum / $zhongjianglv);
            $this->prizeConf['notPirze']['v'] = round($jingduSum * (1 - $zhongjianglv));  //四舍五入
            $finalConf['resNotPrize'] = $this->prizeConf['notPirze'];
        } else {    // 奖品已发完
            $jingduSum = $this->prizeConf['notPirze']['v'] = 1000;
            $finalConf['resNotPrize'] = $this->prizeConf['notPirze'];
        }
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
}