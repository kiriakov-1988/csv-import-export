<?php

namespace App\Controller;


class View
{
    public function getIndexPage()
    {
        $this->generate('main_view.php');
        return true;
    }

    public function getResults()
    {
        echo 'show view of ResultsPage';
        return true;
    }

    private function generate($content_view, $data = null)
    {
        include '../view/template_view.php';
    }

}