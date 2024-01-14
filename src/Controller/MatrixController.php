<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SiteRepository;
use App\Service\SettingsManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MatrixController extends AbstractController
{
    public function __invoke(SettingsManager $settings, SiteRepository $repository, Request $request): Response
    {
        $site = $repository->findAll();

        return $this->render(
            'page/matrix.html.twig',
            [
                'body' => $site[0]->matrix ?? '',
            ]
        );
    }
}
