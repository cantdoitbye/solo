<?php

namespace App\Repositories;

use App\Models\OneOnOneDate;
use App\Repositories\Contracts\OneOnOneDateRepositoryInterface;

class OneOnOneDateRepository implements OneOnOneDateRepositoryInterface
{
    public function create(array $data): OneOnOneDate
    {
        return OneOnOneDate::create($data);
    }

    public function findById(int $id): ?OneOnOneDate
    {
        return OneOnOneDate::find($id);
    }

    public function getByHost(int $hostId, ?string $status = null): array
    {
        $query = OneOnOneDate::byHost($hostId)
                            ->with(['host', 'media'])
                            ->orderBy('event_date', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get()->toArray();
    }

    public function getOneOnOneDates(array $filters = [], int $limit = 10, int $offset = 0): array
    {
        $query = OneOnOneDate::published()
                            ->approved()
                            ->upcoming()
                            ->with(['host', 'media']);

        return $query->orderBy('event_date', 'asc')
                    ->limit($limit)
                    ->offset($offset)
                    ->get()
                    ->toArray();
    }
}

