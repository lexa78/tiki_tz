<?php
namespace app\components;

use yii\base\Behavior;

class CacheBehavior extends Behavior
{
    const SAVE_INTERVAL = 3600;
    public $cacheKey;

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'deleteCache',
            ActiveRecord::EVENT_AFTER_UPDATE => 'deleteCache',
            ActiveRecord::EVENT_AFTER_DELETE => 'deleteCache',
        ];
    }

    public function deleteCache()
    {
        Yii::$app->cache->delete($this->getCacheKey());
    }

    private function getCacheKey() {
        if(empty($this->cacheKey)) {
            $this->cacheKey = $this->owner->tableName();
        }
        return $this->cacheKey;
    }

    public function getCacheByKey($key)
    {
        $cache = $this->getCache();

        if(isset($cache[$key]))
        {
            return $cache[$key];
        }
        return [];
    }

    public function getCache()
    {
        $cache = Yii::$app->cache->get($this->getCacheKey());
        $cacheKey = $this->getCacheKey();
        if($cache === false)
        {
            $temp = (new Query())
                ->select('*')
                ->from($cacheKey)
                ->all();
            $cache= [];
            foreach ($temp as $item)
            {
                $cache[$item->id] = $item;
            }

            Yii::$app->cache->set($cacheKey, $cache, self::SAVE_INTERVAL);
        }

        return $cache;
    }
}

/*
 * ¬ классе модели ActiveRecords прикрепл€етс€ таким образом
 * public function behaviors()
    {
        return [
            'CacheBehavior' => [
                'class' => CacheBehavior::className(),
                'cacheKey' => $this->tableName(),
            ]
        ];
    }
 */