<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestResource;
use App\Models\Quest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserQuestController extends Controller
{
    public function created(Request $request): AnonymousResourceCollection
    {
        $quests = $request->user()->quests()
            ->with(['category', 'creator'])
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'checkpoints'])
            ->latest()
            ->paginate(15);

        return QuestResource::collection($quests);
    }

    public function played(Request $request): AnonymousResourceCollection
    {
        $quests = Quest::query()
            ->whereHas('sessions.participants', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->with(['category', 'creator'])
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'checkpoints'])
            ->latest()
            ->paginate(15);

        return QuestResource::collection($quests);
    }
}
