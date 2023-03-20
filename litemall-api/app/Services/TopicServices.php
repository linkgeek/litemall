<?php

namespace App\Services;

use App\Models\Topic;

class TopicServices extends BaseServices
{
    public function getTopicById(int $topicId)
    {
        return Topic::query()->find($topicId);
    }

    public function getTopicList(int $page, int $limit, $sort, $order, $columns = ['*'])
    {
        $query = Topic::query();
        if (!empty($sort) && !empty($order)) {
            $query = $query->orderBy($sort, $order);
        }

        return $query->paginate($limit, $columns, 'page', $page);
    }

    public function queryFront()
    {
        $limit = SystemServices::getInstance()->getTopicLimit();
        $pageData = $this->getTopicList(1, $limit, 'add_time', 'desc');
        $pageData = $pageData->toArray();
        return $pageData['data'] ?? [];
    }

    public function queryRelatedList()
    {

    }
}
