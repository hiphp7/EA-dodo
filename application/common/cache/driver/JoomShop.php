<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\joom\JoomShop as JoomShopModel;
use app\common\traits\CacheTable;

/**
 * 速卖通账号缓存
 * Created by NetBeans.
 * User: Rondaful
 * Date: 2018/1/2
 * Time: 19:43
 */
class JoomShop extends Cache
{
    const cachePrefix = 'table';
    private $taskPrefix = 'task:joom:';
    private $listingSyncTime = 'listing_sync_time';
    private $listintlistkey = 'listing_list';

    use CacheTable;

    public function __construct()
    {
        $this->model(JoomShopModel::class);
        parent::__construct();
    }

    /**
     * Aliexpress账号获取listing最后更新时间
     * @param int $accountId
     * @return int
     */
    public function getListingSyncTime($accountId)
    {
        $key = $this->taskPrefix . $this->listingSyncTime;
        if ($this->persistRedis->hexists($key, $accountId)) {
            $arr = $this->persistRedis->hget($key, $accountId);
            return $arr ?? 0;
        }
        return 0;
    }

    /**
     * 设置Aliexpress账号同步listing的时间
     * @param int $account_id
     * @param int $syncTime
     * @return array|mixed
     */
    public function setListingSyncTime($account_id, $syncTime)
    {
        $key = $this->taskPrefix . $this->listingSyncTime;
        if (!empty($syncTime)) {
            $this->persistRedis->hset($key, $account_id, $syncTime);
        }
    }

    /**
     * 获取该帐号店铺的listint列表；
     * @param $accountId
     * @return array|mixed|string
     */
    public function getListinglist($accountId)
    {
        $key = $this->taskPrefix . $this->listintlistkey;
        if ($this->persistRedis->hexists($key, $accountId)) {
            $arr = $this->persistRedis->hget($key, $accountId);
            $arr = empty($arr)? ['product' => [], 'info' => [], 'variant' => []] : json_decode($arr, true);
            return $arr;
        }
        return ['product' => [], 'info' => [], 'variant' => []];
    }

    /**
     * @title 保存该帐号店铺的listint列表,分3种形式来保存
     * @param $shop_id 店铺id
     * @param $data ['key' => , 'value' =>]
     * @param $type 'product', 'info', 'variant'
     * @return bool
     */
    public function addListingdata($shop_id, $data, $type) {
        if(!in_array($type, ['product', 'info', 'variant'])) {
            return false;
        }
        if(!isset($data['key'], $data['value'])) {
            return false;
        }

        $key = $this->taskPrefix . $this->listintlistkey;
        if (!empty($syncTime)) {
            $old = $this->getListinglist($shop_id);
            $old[$type][$data['key']] = $data['value'];
            $this->persistRedis->hset($key, $shop_id, json_encode($old));
        }
        return true;
    }

    /**
     * 获取全部账号信息
     * @param int $id
     * @return array|mixed
     */
    public function getAllAccounts()
    {
        return $this->getTableRecord();
    }

    /**
     * 获取账号信息通过id
     * @param int $id
     * @return array|mixed
     */
    public function getAccountById($id)
    {
        return $this->getTableRecord($id);
    }

    /**
     * 获取帐号信息传ID为，此ID的，不传ID，则为全部；
     * @param $id
     * @return array|mixed
     */
    public function getAccount($id = 0)
    {
        return $this->getTableRecord($id);
    }

    /**
     * 删除账号缓存信息
     * @param int $id
     */
    public function delAccount($id = 0)
    {
        $this->delTableRecord($id);
        return true;
    }

    /**
     * 拿到 某个店铺 的 账号ID
     * @param int $shop_id
     */
    public function getAccountId($shopId =1)
    {
        $shop = $this->getTableRecord($shopId);
        return $shop['joom_account_id'] ?? 0; // joom 账号 ID
    }

    /**
     * 拿到 某个 账号ID  的 店铺
     * @param int $accountId
     * @param string $filed 店铺信息
     */
    public function getAllShopByAccountId($accountId,$filed = 'id,joom_account_id,code,shop_name,is_invalid'){
        $filter[] = ['is_invalid', '==', 1];
        $filter[] = ['joom_account_id', '==', $accountId];
        return Cache::filter(Cache::store('JoomShop')->getAllAccounts(), $filter, $filed);
    }

    public function getTableRecord($id = 0)
    {
        $recordData = [];
        if (!empty($id)) {
            $key = $this->tableRecordPrefix . $id;
            if ($this->isExists($key)) {
                $info = $this->cacheObj()->hGetAll($key);
            } else {
                $info = $this->readTable($id);
            }
            $recordData = $info;
        } else {
            $recordData = $this->readTable($id, false);
        }
        return $recordData;
    }



}