<?php


namespace App\Services;

use Kreait\Firebase\Database;

class Firebase
{
    public $database;
    public function __construct(Database $database)
    {
        $this->database = $database;
    }


    public function getReference()
    {
        $reference = $this->database->getReference('desafiodarko/firebaseio/com');
    }

}