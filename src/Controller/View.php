<?php

namespace App\Controller;


class View
{
    public function getIndexPage()
    {
        echo 'show view of IndexPage';
        return true;
    }

    public function getResults()
    {
        echo 'show view of ResultsPage';
        return true;
    }

}