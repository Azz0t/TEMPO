<?php

namespace App\Controller;

use App\Entity\Board;
use App\Repository\BoardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\BoardColumn;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Card;


final class BoardController extends AbstractController
{
    #[Route('/board', name: 'app_board')]
    public function index(BoardRepository $boardRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $boards = $boardRepository->findBy(
            ['owner' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('board/index.html.twig', [
            'boards' => $boards,
        ]);
    }

    /***| new|***/
    #[Route('/board/new', name: 'app_board_new', methods: ['GET'])]
    public function new(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $board = new Board();
        $board->setName('My new Kanban');
        $board->setStartDate(new \DateTimeImmutable('today'));
        $board->setEndDate(new \DateTimeImmutable('+30 days'));
        $board->setOwner($this->getUser());

        $entityManager->persist($board);

        $columns = ['To do', 'In progress', 'Done🎉'];

        foreach ($columns as $position => $name) {
            $column = new BoardColumn();
            $column->setName($name);
            $column->setPosition($position + 1);
            $column->setBoard($board);

            $column->setIsLocked($name === 'Done🎉');

            $entityManager->persist($column);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_board_show', [
            'id' => $board->getId(),
        ]);
    }

    #[Route('/board/{id}', name: 'app_board_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Board $board): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($board->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('board/show.html.twig', [
            'board' => $board,
        ]);
    }

    /***| rename |***/
    #[Route('/board/{id}/rename', name: 'app_board_rename', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function rename(
        Board $board,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($board->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');
        $name = trim((string) $request->request->get('name', ''));

        if (!$this->isCsrfTokenValid(
            'rename-board-' . $board->getId(),
            is_string($token) ? $token : ''
        )) {
            return $this->json([
                'success' => false,
                'message' => 'Session invalide. Recharge la page.',
            ], 403);
        }

        if ($name === '') {
            return $this->json([
                'success' => false,
                'message' => 'Le nom du board ne peut pas être vide.',
            ], 422);
        }

        if (mb_strlen($name) > 150) {
            return $this->json([
                'success' => false,
                'message' => 'Le nom ne peut pas dépasser 150 caractères.',
            ], 422);
        }

        $board->setName($name);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'name' => $board->getName(),
        ]);
    }

    /***| dates |***/
    #[Route(
    '/board/{id}/dates',
    name: 'app_board_update_dates',
    requirements: ['id' => '\d+'],
    methods: ['POST']
    )]
    public function updateDates(
        Board $board,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($board->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');

        if (
            !is_string($token)
            || !$this->isCsrfTokenValid('update-dates-' . $board->getId(), $token)
        ) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $startValue = $request->request->get('startDate');
        $endValue = $request->request->get('endDate');

        if (!is_string($startValue) || !is_string($endValue)) {
            $this->addFlash('error', 'Les dates sont invalides.');

            return $this->redirectToRoute('app_board_show', [
                'id' => $board->getId(),
            ]);
        }

        $startDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $startValue);
        $endDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $endValue);

        if (
            !$startDate
            || !$endDate
            || $startDate->format('Y-m-d') !== $startValue
            || $endDate->format('Y-m-d') !== $endValue
            || $endDate < $startDate
        ) {
            $this->addFlash(
                'error',
                'Vérifie les dates : la date de fin doit être égale ou postérieure à la date de début.'
            );

            return $this->redirectToRoute('app_board_show', [
                'id' => $board->getId(),
            ]);
        }

        $board->setStartDate($startDate);
        $board->setEndDate($endDate);

        $entityManager->flush();

        $this->addFlash('success', 'Les dates du projet ont été mises à jour.');

        return $this->redirectToRoute('app_board_show', [
            'id' => $board->getId(),
        ]);
    }
    
    /***| delete board |***/
    #[Route(
    '/board/{id}/delete',
    name: 'app_board_delete',
    requirements: ['id' => '\d+'],
    methods: ['POST']
    )]
    public function delete(
        Board $board,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($board->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');

        if (
            !is_string($token)
            || !$this->isCsrfTokenValid('delete-board-' . $board->getId(), $token)
        ) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $entityManager->remove($board);
        $entityManager->flush();

        $this->addFlash('success', 'Le projet a été supprimé.');

        return $this->redirectToRoute('app_board');
    }


    /***| rename colone |***/
    #[Route(
        '/board/column/{id}/rename',
        name: 'app_board_column_rename',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function renameColumn(
        BoardColumn $column,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Vérifier que l'utilisateur est propriétaire du board
        if ($column->getBoard()->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($column->getName() === 'Done🎉' && $column->isLocked()) {
            return $this->json([
                'success' => false, 
                'message' => 'La colonne Terminé est verrouillée définitivement.',
                'isLocked' => true,
            ], 423);
        }

        // Empêcher le renommage si la colonne est verrouillée
        if ($column->isLocked()) {
            return $this->json([
                'success' => false,
                'message' => 'Cette colonne est verrouillée. Déverrouille-la pour modifier son nom.',
            ], 423);
        }

        // Vérifier le jeton CSRF
        $token = $request->request->get('_token');

        if (
            !is_string($token)
            || !$this->isCsrfTokenValid(
                'rename-column-' . $column->getId(),
                $token
            )
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Session invalide. Recharge la page.',
            ], 403);
        }

        // Valider le nom
        $name = trim((string) $request->request->get('name', ''));

        if ($name === '' || mb_strlen($name) > 150) {
            return $this->json([
                'success' => false,
                'message' => 'Le titre doit contenir entre 1 et 150 caractères.',
            ], 422);
        }

        // Enregistrer le nouveau nom
        $column->setName($name);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'name' => $column->getName(),
        ]);
    }


    /***| delete colone |***/
    #[Route(
        '/board/column/{id}/delete',
        name: 'app_board_column_delete',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function deleteColumn(
        BoardColumn $column,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($column->getBoard()->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($column->isLocked()) {
            throw $this->createAccessDeniedException(
                'Cette colonne est verrouillée. Déverrouille-la pour la supprimer.'
            );
        }

        $token = $request->request->get('_token');

        if (
            !is_string($token)
            || !$this->isCsrfTokenValid(
                'delete-column-' . $column->getId(),
                $token
            )
        ) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $boardId = $column->getBoard()->getId();

        $entityManager->remove($column);
        $entityManager->flush();

        $this->addFlash('success', 'La colonne a été supprimée.');

        return $this->redirectToRoute('app_board_show', [
            'id' => $boardId,
        ]);
    }


    /***| DRAG AND DROP DES COLONNES |***/
    #[Route(
        '/board/{id}/columns/reorder',
        name: 'app_board_columns_reorder',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function reorderColumns(
        Board $board,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($board->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json([
                'success' => false,
                'message' => 'Requête invalide.',
            ], 400);
        }

        $token = $payload['_token'] ?? null;
        $ids = $payload['ids'] ?? null;

        if (
            !is_string($token)
            || !$this->isCsrfTokenValid(
                'reorder-columns-' . $board->getId(),
                $token
            )
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Jeton de sécurité invalide.',
            ], 403);
        }

        if (!is_array($ids) || !array_is_list($ids)) {
            return $this->json([
                'success' => false,
                'message' => 'Ordre des colonnes invalide.',
            ], 400);
        }

        $columns = $board->getBoardColumns()->toArray();

        usort(
            $columns,
            static fn (BoardColumn $a, BoardColumn $b): int =>
                $a->getPosition() <=> $b->getPosition()
        );

        $columnsById = [];

        foreach ($columns as $column) {
            $columnsById[$column->getId()] = $column;
        }

        $expectedIds = array_map(
            static fn (BoardColumn $column): int => $column->getId(),
            $columns
        );

        $newIds = [];

        foreach ($ids as $id) {
            if (!is_int($id) || $id <= 0) {
                return $this->json([
                    'success' => false,
                    'message' => 'Identifiant de colonne invalide.',
                ], 400);
            }

            $newIds[] = $id;
        }

        $sortedExpected = $expectedIds;
        $sortedNew = $newIds;

        sort($sortedExpected);
        sort($sortedNew);

        if (
            count($newIds) !== count(array_unique($newIds))
            || $sortedExpected !== $sortedNew
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Les colonnes envoyées ne correspondent pas à ce board.',
            ], 400);
        }

        // Vérifier qu'aucune colonne verrouillée ne change de position.
        foreach ($newIds as $index => $id) {
            $column = $columnsById[$id];

            if (
                $column->isLocked()
                && $column->getPosition() !== $index + 1
            ) {
                return $this->json([
                    'success' => false,
                    'message' => 'Une colonne verrouillée ne peut pas être déplacée.',
                ], 409);
            }
        }

        // Enregistrer le nouvel ordre.
        foreach ($newIds as $index => $id) {
            $columnsById[$id]->setPosition($index + 1);
        }

        $entityManager->flush();

        return $this->json(['success' => true]);
    }



    
    #[Route(
        '/board/column/{id}/toggle-lock',
        name: 'app_board_column_toggle_lock',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function toggleColumnLock(
        BoardColumn $column,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($column->getBoard()->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');

        if (
            !is_string($token)
            || !$this->isCsrfTokenValid(
                'lock-column-' . $column->getId(),
                $token
            )
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Jeton de sécurité invalide. Recharge la page.',
            ], 403);
        }

        $column->setIsLocked(!$column->isLocked());

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'isLocked' => $column->isLocked(),
        ]);
    }


    
    #[Route(
        '/board/column/{id}/cards',
        name: 'app_board_card_create',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function createCard(
        BoardColumn $column,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Vérifier que l'utilisateur possède le board.
        if ($column->getBoard()->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier le jeton CSRF.
        $token = $request->request->get('_token');

        if (
            !is_string($token)
            || !$this->isCsrfTokenValid(
                'create-card-' . $column->getId(),
                $token
            )
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Jeton de sécurité invalide. Recharge la page.',
            ], 403);
        }

        // Trouver la prochaine position dans cette colonne.
        $lastCard = $entityManager->getRepository(Card::class)->findOneBy(
            ['boardColumn' => $column],
            ['position' => 'DESC']
        );

        $position = $lastCard ? $lastCard->getPosition() + 1 : 1;

        // Créer la carte.
        $card = new Card();
        $card->setTitle('Titre de carte');
        $card->setPosition($position);
        $card->setStartDate(new \DateTimeImmutable('today'));
        $card->setCreatedAt(new \DateTimeImmutable());
        $card->setBoardColumn($column);
        $card->setCreatedBy($this->getUser());

        // Enregistrer en base de données.
        $entityManager->persist($card);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'card' => [
                'id' => $card->getId(),
                'title' => $card->getTitle(),
                'position' => $card->getPosition(),
            ],
        ], 201);
    }



    
    #[Route(
        '/board/card/{id}/move',
        name: 'app_board_card_move',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function moveCard(
        Card $card,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $data = $request->toArray();
        } catch (\JsonException) {
            return $this->json([
                'success' => false,
                'message' => 'Requête invalide.',
            ], 400);
        }

        $sourceColumn = $card->getBoardColumn();
        $targetColumn = $entityManager->getRepository(BoardColumn::class)
            ->find($data['targetColumnId'] ?? 0);

        if (!$sourceColumn || !$targetColumn) {
            return $this->json([
                'success' => false,
                'message' => 'Colonne introuvable.',
            ], 404);
        }

        $board = $sourceColumn->getBoard();

        // Les deux colonnes doivent appartenir au même board.
        if (
            $targetColumn->getBoard()->getId() !== $board->getId()
            || $board->getOwner() !== $this->getUser()
        ) {
            throw $this->createAccessDeniedException();
        }

        $token = $data['_token'] ?? null;

        if (
            !is_string($token)
            || !$this->isCsrfTokenValid(
                'move-cards-' . $board->getId(),
                $token
            )
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Jeton de sécurité invalide.',
            ], 403);
        }

        $sourceIds = $data['sourceCardIds'] ?? null;
        $targetIds = $data['targetCardIds'] ?? null;

        if (!is_array($sourceIds) || !is_array($targetIds)) {
            return $this->json([
                'success' => false,
                'message' => 'Ordre des cartes invalide.',
            ], 400);
        }

        $cardRepository = $entityManager->getRepository(Card::class);

        // Déplacement dans la même colonne.
        if ($sourceColumn->getId() === $targetColumn->getId()) {
            $ids = $targetIds;

            if (
                count($ids) !== count(array_unique($ids))
                || !in_array($card->getId(), $ids, false)
            ) {
                return $this->json([
                    'success' => false,
                    'message' => 'Ordre des cartes invalide.',
                ], 400);
            }

            foreach ($ids as $index => $id) {
                $item = $cardRepository->find($id);

                if (!$item || $item->getBoardColumn()->getId() !== $sourceColumn->getId()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Carte invalide dans cet ordre.',
                    ], 400);
                }

                $item->setPosition($index + 1);
            }
        } else {
            // Vérifier les cartes qui restent dans la colonne source.
            if (
                in_array($card->getId(), $sourceIds, false)
                || in_array($card->getId(), $targetIds, false) === false
                || count($sourceIds) !== count(array_unique($sourceIds))
                || count($targetIds) !== count(array_unique($targetIds))
            ) {
                return $this->json([
                    'success' => false,
                    'message' => 'Ordre des cartes invalide.',
                ], 400);
            }

            foreach ($sourceIds as $index => $id) {
                $item = $cardRepository->find($id);

                if (!$item || $item->getBoardColumn()->getId() !== $sourceColumn->getId()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Carte source invalide.',
                    ], 400);
                }

                $item->setPosition($index + 1);
            }

            foreach ($targetIds as $index => $id) {
                $item = $cardRepository->find($id);

                if (!$item) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Carte cible introuvable.',
                    ], 400);
                }

                // La carte déplacée est encore liée à la source en base.
                if ($item->getId() === $card->getId()) {
                    $item->setBoardColumn($targetColumn);
                } elseif ($item->getBoardColumn()->getId() !== $targetColumn->getId()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Carte cible invalide.',
                    ], 400);
                }

                $item->setPosition($index + 1);
            }
        }

        $entityManager->flush();

        return $this->json(['success' => true]);
    }


    #[Route(
        '/board/card/{id}/edit',
        name: 'app_board_card_edit',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function editCard(
        Card $card,
        EntityManagerInterface $entityManager,
        \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $csrfTokenManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $column = $card->getBoardColumn();

        if (!$column || $column->getBoard()->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $members = $entityManager
            ->getRepository(\App\Entity\BoardMember::class)
            ->findBy(['board' => $column->getBoard()]);

        $memberData = [];

        foreach ($members as $member) {
            $user = $member->getUser();

            $memberData[] = [
                'id' => $member->getId(),
                'name' => $user->getUserIdentifier(),
            ];
        }

        return $this->json([
            'success' => true,
            'token' => $csrfTokenManager->getToken('edit-card-' . $card->getId())->getValue(),
            'members' => $memberData,
            'card' => [
                'id' => $card->getId(),
                'title' => $card->getTitle(),
                'description' => $card->getDescription(),
                'dueDate' => $card->getDueDate()?->format('Y-m-d'),
                'priority' => $card->getPriority(),
                'assignedMembers' => $card->getAssignedMembers()
                    ->map(static fn ($member) => $member->getId())
                    ->toArray(),
            ],
        ]);
    }


    #[Route(
        '/board/card/{id}/update',
        name: 'app_board_card_update',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function updateCard(
        Card $card,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $column = $card->getBoardColumn();

        if (
            !$column
            || $column->getBoard()->getOwner() !== $this->getUser()
        ) {
            throw $this->createAccessDeniedException();
        }

        try {
            $data = $request->toArray();
        } catch (\JsonException) {
            return $this->json([
                'success' => false,
                'message' => 'Requête invalide.',
            ], 400);
        }

        $token = $data['_token'] ?? null;

        if (
            !is_string($token)
            || !$this->isCsrfTokenValid('edit-card-' . $card->getId(), $token)
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Jeton de sécurité invalide. Recharge la page.',
            ], 403);
        }

        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $priority = $data['priority'] ?? 'low';
        $dueDateValue = $data['dueDate'] ?? null;
        $memberIds = $data['assignedMembers'] ?? [];

        if ($title === '' || mb_strlen($title) > 255) {
            return $this->json([
                'success' => false,
                'message' => 'Le titre doit contenir entre 1 et 255 caractères.',
            ], 422);
        }

        if (
            !is_string($priority)
            || !in_array($priority, ['low', 'medium', 'high', 'highest'], true)
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Priorité invalide.',
            ], 422);
        }

        if (!is_array($memberIds)) {
            return $this->json([
                'success' => false,
                'message' => 'Liste des membres invalide.',
            ], 422);
        }

        $dueDate = null;

        if ($dueDateValue !== null && $dueDateValue !== '') {
            if (!is_string($dueDateValue)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Date d’échéance invalide.',
                ], 422);
            }

            $dueDate = \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $dueDateValue
            );

            if (
                !$dueDate
                || $dueDate->format('Y-m-d') !== $dueDateValue
            ) {
                return $this->json([
                    'success' => false,
                    'message' => 'Date d’échéance invalide.',
                ], 422);
            }
        }

        // Mettre à jour les champs simples.
        $card->setTitle($title);
        $card->setDescription($description !== '' ? $description : null);
        $card->setPriority($priority);
        $card->setDueDate($dueDate);

        // Remplacer les membres assignés.
        // Les identifiants doivent correspondre à des BoardMember du tableau.
        $boardMembers = $entityManager
            ->getRepository(\App\Entity\BoardMember::class)
            ->findBy(['board' => $column->getBoard()]);

        $membersById = [];

        foreach ($boardMembers as $member) {
            $membersById[$member->getId()] = $member;
        }

        $card->getAssignedMembers()->clear();

        foreach ($memberIds as $memberId) {
            if (
                (!is_int($memberId) && !ctype_digit((string) $memberId))
                || !isset($membersById[(int) $memberId])
            ) {
                return $this->json([
                    'success' => false,
                    'message' => 'Un membre sélectionné ne fait pas partie de ce tableau.',
                ], 422);
            }

            $card->addAssignedMember($membersById[(int) $memberId]);
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Carte mise à jour.',
            'card' => [
                'id' => $card->getId(),
                'title' => $card->getTitle(),
                'description' => $card->getDescription(),
                'dueDate' => $card->getDueDate()?->format('Y-m-d'),
                'priority' => $card->getPriority(),
                'assignedMembers' => $card->getAssignedMembers()
                    ->map(
                        static fn ($member) => $member->getId()
                    )
                    ->toArray(),
            ],
        ]);
    }

}
