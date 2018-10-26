<?php

namespace App\Controller;


use App\Model\DB;

class View
{
    public function getIndexPage()
    {
        $this->generate('main_view.php');
        return true;
    }

    public function getResults()
    {
        $db = new DB();
        $csv = $db->getCsvData();

        $data = [
            'title' => 'CSV result from DB',
            'csv' => $csv
        ];

        $this->generate('results_view.php', $data);
        return true;
    }

    private function generate(string $content_view, array $data = null)
    {
        include '../view/template_view.php';
    }

}