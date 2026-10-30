<?php

namespace App\Controller;

use App\Repository\BoardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(BoardRepository $boardRepository): Response
    {
        $boards = [];

        if ($this->getUser()) {
            $boards = $boardRepository->findBy(
                ['owner' => $this->getUser()],
                ['createdAt' => 'DESC']
            );
        }

        return $this->render('home/index.html.twig', [
            'boards' => $boards,
        ]);
    }
}