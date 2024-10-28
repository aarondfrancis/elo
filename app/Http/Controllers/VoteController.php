<?php

namespace App\Http\Controllers;

use App\Events\RatingsUpdated;
use App\Services\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Sanity\Client;

class VoteController extends Controller
{
    public function __construct(public readonly Client $sanity)
    {
        //
    }

    public function store(Request $request)
    {
        // Sanity IDs
        $toyA = $request->input('a_toy_id');
        $toyB = $request->input('b_toy_id');

        // Ratings from the frontend. We just use em for optimistic UI.
        $toyARating = $request->float('a_toy_rating');
        $toyBRating = $request->float('b_toy_rating');

        // Whether Toy A won, lost, or drew
        $result = $request->float('a_result');

        // Run this after the request to make it feel super snappy.
        defer(fn() => $this->calculateAndBroadcast($toyA, $toyB, $result));

        // These are should be right, but could potentially be wrong.
        // Doesn't matter, the absolutely correct stuff
        // gets broadcast to the frontend later.
        $optimistic = $this->calculate($toyA, $toyARating, $toyB, $toyBRating, $result);

        // Immediately return the optimistic results.
        return response()->json([
            $toyA => $optimistic[0],
            $toyB => $optimistic[1],
        ]);
    }

    protected function calculateAndBroadcast($toyA, $toyB, $result)
    {
        // Get the real ratings from our source of truth (Sanity)
        $ratings = $this->sanity->fetch(
            '*[_id in [$a, $b]]{_id,rating}', ['a' => $toyA, 'b' => $toyB]
        );

        $ratings = Arr::pluck($ratings, 'rating', '_id');

        // Calculate the new, real ratings
        $ratings = $this->calculate($toyA, $ratings[$toyA], $toyB, $ratings[$toyB], $result);

        // Patch the documents in Sanity
        $patchA = $this->sanity->patch($toyA)->set(['rating' => $ratings[0]]);
        $patchB = $this->sanity->patch($toyB)->set(['rating' => $ratings[1]]);

        // But do it all in one request.
        $this->sanity->transaction()->patch($patchA)->patch($patchB)->commit();

        // Send a new even to the frontend to update the list
        broadcast(new RatingsUpdated([
            $toyA => $ratings[0],
            $toyB => $ratings[1]
        ]));
    }

    protected function calculate($toyA, $toyARating, $toyB, $toyBRating, $toyAResult)
    {
        $results = match ($toyAResult) {
            1.0 => [Rating::WIN, Rating::LOST],
            0.5 => [Rating::DRAW, Rating::DRAW],
            0.0 => [Rating::LOST, Rating::WIN],
        };

        $rating = new Rating($toyARating, $toyBRating, ...$results);

        return array_map('intval', array_values($rating->getNewRatings()));
    }
}
