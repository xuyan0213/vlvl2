<?php


namespace App\Models\Sswh\X201106;


use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'x201106_user';

    protected $guarded = [];

    /*
    * 处理抽奖 -- 开始
    * #######################################################################################################################################################
    * */
    private $zhongJiangLv = 0.91;  //设置中奖率 如果大于 1 始终会转化为0~1之间的小数
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
    /*
     * 奖品：
            海底捞免费体验券：       20 张
            恒温泳池体验券：        100 张
            大象公仔：             10 个
            专属定制时尚鼠标垫：     20 个
            专属定制暖心抱枕被：     50 个
            专属定制U型枕：         50 个
            专属定制帆布袋：         100个
     */
    protected $prizeConf = [
        /*中奖数组*/
        'prize' => [
            0 => ['prize_id' => 1, 'prize_level_name' => '七等奖', 'prize_name' => '恒温泳池体验券', 'v' => 100, 'count' => 0, 'limit' => 0],
            1 => ['prize_id' => 2, 'prize_level_name' => '六等奖', 'prize_name' => '专属定制帆布袋', 'v' => 100, 'count' => 0, 'limit' => 0],
            2 => ['prize_id' => 3, 'prize_level_name' => '五等奖', 'prize_name' => '专属定制U型枕', 'v' => 50, 'count' => 0, 'limit' => 0],
            3 => ['prize_id' => 4, 'prize_level_name' => '四等奖', 'prize_name' => '专属定制暖心抱枕被', 'v' => 50, 'count' => 0, 'limit' => 0],
            4 => ['prize_id' => 5, 'prize_level_name' => '三等奖', 'prize_name' => '专属定制时尚鼠标垫', 'v' => 20, 'count' => 0, 'limit' => 0],
            5 => ['prize_id' => 6, 'prize_level_name' => '二等奖', 'prize_name' => '海底捞免费体验券', 'v' => 20, 'count' => 0, 'limit' => 0],
            6 => ['prize_id' => 7, 'prize_level_name' => '一等奖', 'prize_name' => '大象公仔', 'v' => 10, 'count' => 0, 'limit' => 0],
        ],
        /*不中奖   --未中奖*/
        'notPrize' => ['prize_id' => 0, 'prize_level_name' => '未中奖', 'prize_name' => '未中奖', 'v' => 100, 'count' => 0, 'limit' => 100000],
    ];

    protected $prizeConfLimit = [
        /*测试*/
        'test' => [
            '0' => ['prize_id' => 0, 'limit' => 100000],
            '1' => ['prize_id' => 1, 'limit' => 10],
            '2' => ['prize_id' => 2, 'limit' => 10],
            '3' => ['prize_id' => 3, 'limit' => 10],
            '4' => ['prize_id' => 4, 'limit' => 10],
            '5' => ['prize_id' => 5, 'limit' => 10],
            '6' => ['prize_id' => 6, 'limit' => 10],
            '7' => ['prize_id' => 7, 'limit' => 10],
        ],

        /*正式*/
        'formal' => [
            '0' => ['prize_id' => 0, 'limit' => 100000],
            '1' => ['prize_id' => 1, 'limit' => 100],
            '2' => ['prize_id' => 2, 'limit' => 100],
            '3' => ['prize_id' => 3, 'limit' => 50],
            '4' => ['prize_id' => 4, 'limit' => 50],
            '5' => ['prize_id' => 5, 'limit' => 20],
            '6' => ['prize_id' => 6, 'limit' => 20],
            '7' => ['prize_id' => 7, 'limit' => 10],
        ],
    ];

    /*
     * 获取中奖奖品的数量
     */
    public function getPrizeNum($prizeId, $version)
    {
        return $this->prizeConfLimit[$version][$prizeId]['limit'];
    }

    /*
     * 随机抽奖  中奖几率始终不变
     * */
    public function fixRandomPrize($redisCountKey, $version,$score=100)
    {
        $zhongjianglv = $this->parseZhongJiangLv($this->zhongJiangLv); //解析中奖率 防止出错
        $quanZhong = 100;
        $finalConf = []; //最终生成的配置数组
        $jingduSum = 0; //精度计数
        $resultPrize = null; // 最终的中奖的信息
        $prizeCountKey = $redisCountKey . ':' . $version;
        $redis = app('redis');
        $redis->select(12);
        if (!$prizeCountArr = $redis->hGetAll($prizeCountKey)) {
//            dd($prizeCountKey);
            throw new \Exception('缓存获取中奖统计失败', -2);
        }
        if ($score > 300) {
            $this->prizeConf['prize'][5]['v'] = 1000;
        }
        //配置
        foreach ($this->prizeConf['prize'] as $k => $arr) {
            $moneyStr = strval($arr['prize_id']);
            $this->prizeConf['prize'][$k]['count'] = $prizeCountArr[$moneyStr];
            $this->prizeConf['prize'][$k]['limit'] = $this->prizeConfLimit[$version][$moneyStr]['limit'];
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
            $this->prizeConf['notPrize']['v'] = round($jingduSum * (1 - $zhongjianglv));  //四舍五入
            $finalConf['resNotPrize'] = $this->prizeConf['notPrize'];
        } else {    // 奖品已发完
            $jingduSum = $this->prizeConf['notPrize']['v'] = 1000;
            $finalConf['resNotPrize'] = $this->prizeConf['notPrize'];
        }
        /*计算百分比*/
        foreach ($finalConf as $k => $arr) {
            $finalConf[$k]['v100'] = round($arr['v'] * 100 / $jingduSum, 2).'%';
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
        return [
            'resultPrize' => $resultPrize, 'finalConf' => $finalConf, 'prizeConf' => $this->prizeConf,
            'prizeCountKey' => $prizeCountKey
        ];
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