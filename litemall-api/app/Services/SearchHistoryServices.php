<?php

namespace App\Services;

use App\Models\SearchHistory;
use App\Services\Exception;

class SearchHistoryServices extends BaseServices
{
    public function save($userId, $keyword, $from)
    {
        $history = new SearchHistory();
        $history->fill([
            'user_id' => $userId,
            'keyword' => $keyword,
            'from'    => $from,
        ]);
        $history->save();
        return $history;
    }
}
