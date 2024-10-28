<?php

namespace App\Console\Commands;

use App\Events\RatingsUpdated;
use App\Services\Rating;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Sanity\Client;

class Test extends Command
{
    public Client $sanity;

    protected $signature = 't';

    protected $description = 'Test';

    public function handle(Client $sanity)
    {
        $this->sanity = $sanity;

        $this->setRatings();
        return;

//        $results = $this->sanity->fetch('*[_type=="toy"]{_id,name,image,rating}');
//        dd($results);


        $toyA = 1400;
        $toyB = 1400;

        foreach (range(0, 100) as $i) {
            [$toyA, $toyB] = $this->play($toyA, $toyB);

            $this->line('Toy A:' . $toyA);
            $this->line('Toy B:' . $toyB);
            $this->line('----------------------------');
        }
    }

    public function setRatings()
    {
        // Set the starting scores
        $results = $this->sanity->fetch('*[_type in ["toy", "person"] && defined(image)]{_id,name,image,rating}');

        foreach ($results as $result) {
            $this->sanity->patch($result['_id'])->set(['rating' => 1400])->commit();
        }
    }


    public function play($toyA, $toyB)
    {
        $outcomes = [
            [Rating::WIN, Rating::LOST],
            [Rating::LOST, Rating::WIN],
            [Rating::DRAW, Rating::DRAW],
        ];

        $int = random_int(0, 2);

        $line = match ($int) {
            0 => 'Toy 1: Won.  Toy 2: Lost.',
            1 => 'Toy 1: Lost. Toy 2: Won.',
            2 => 'Toy 1: Draw. Toy 2: Draw.'
        };

        $this->line($line);

        $outcome = $outcomes[$int];

        $rating = new Rating($toyA, $toyB, ...$outcome);

        return array_values($rating->getNewRatings());
    }
}
