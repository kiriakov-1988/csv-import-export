<?php

namespace App\Controller;


use App\Model\DB;

class View
{
    public function getIndexPage(): bool
    {
        $this->generate('main_view.php');
        return true;
    }

    public function getResults(): bool
    {
        try {
            $db = new DB();
            $csv = $db->getCsvData();
            $error = false;
        } catch (\PDOException $e) {
            $csv = [];
            $error = $e->getMessage();
        }


        $data = [
            'title' => 'CSV result from DB',
            'csv'   => $csv,
            'error' => $error
        ];

        $this->generate('results_view.php', $data);
        return true;
    }

    public function showError(): void
    {
        $this->generate('404-error_view.php', [
            'title' => 'Ошибка 404',
        ]);
    }

    private function generate(string $content_view, array $data = null): void
    {
        include '../view/template_view.php';
    }

}