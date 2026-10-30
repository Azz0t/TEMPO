
document.addEventListener('DOMContentLoaded', () => {
    const input = document.querySelector('#board-title');

    const menuToggle = document.querySelector('#board-menu-toggle');
    const menuDropdown = document.querySelector('#board-menu-dropdown');
    const editTitleButton = document.querySelector('#edit-board-title');

    if (!input) {
        return;
    }

    let originalName = input.value;
    let saving = false;

    // Ouvrir et fermer le menu
    const closeMenu = () => {
        if (!menuDropdown || !menuToggle) return;

        menuDropdown.hidden = true;
        menuToggle.setAttribute('aria-expanded', 'false');
    };

    if (menuToggle && menuDropdown) {
        menuToggle.addEventListener('click', (event) => {
            event.stopPropagation();

            const isOpen = !menuDropdown.hidden;

            menuDropdown.hidden = isOpen;
            menuToggle.setAttribute('aria-expanded', String(!isOpen));
        });

        // Fermer le menu en cliquant ailleurs
        document.addEventListener('click', (event) => {
            if (
                !menuDropdown.contains(event.target) &&
                !menuToggle.contains(event.target)
            ) {
                closeMenu();
            }
        });
    }

    // Activer la modification du titre
    const enableTitleEditing = () => {
        if (saving) return;

        input.removeAttribute('readonly');
        input.focus();
        input.select();
    };

    if (editTitleButton) {
        editTitleButton.addEventListener('click', () => {
            closeMenu();
            enableTitleEditing();
        });
    }

    // Double-clic sur le titre : conserver cette possibilité
    input.addEventListener('dblclick', enableTitleEditing);

    // Entrée pour enregistrer, Échap pour annuler
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !input.readOnly) {
            event.preventDefault();
            input.blur();
        }

        if (event.key === 'Escape' && !input.readOnly) {
            input.value = originalName;
            input.setAttribute('readonly', '');
            input.blur();
        }
    });

    // Sauvegarder lorsque le champ perd le focus
    input.addEventListener('blur', async () => {
        if (input.readOnly || saving) {
            return;
        }

        const newName = input.value.trim();

        input.setAttribute('readonly', '');

        if (!newName) {
            input.value = originalName;
            return;
        }

        if (newName === originalName) {
            return;
        }

        saving = true;

        const data = new URLSearchParams();
        data.set('name', newName);
        data.set('_token', input.dataset.token);

        try {
            const response = await fetch(input.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: data.toString(),
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Unable to save the board title.'
                );
            }

            input.value = result.name;
            originalName = result.name;

            document.title = result.name + ' - Tempo';
        } catch (error) {
            input.value = originalName;
            alert(error.message || 'Error while saving the title.');
        } finally {
            saving = false;
        }
    });
});



/*colone */
// =====| MENUS ET TITRES DES COLONNES |=====
document.querySelectorAll('.board-column').forEach((column) => {
    const menuToggle = column.querySelector('.column-menu');
    const menuDropdown = column.querySelector('.column-menu-dropdown');
    const editButton = column.querySelector('.column-edit-title');
    const lockButton = column.querySelector('.column-lock-button');
    const lockLabel = column.querySelector('.column-lock-label');
    const titleInput = column.querySelector('.column-title-input');

    if (!menuToggle || !menuDropdown || !titleInput) return;

    let originalName = titleInput.value;
    let saving = false;

    const closeMenu = () => {
        menuDropdown.hidden = true;
        menuToggle.setAttribute('aria-expanded', 'false');
    };

    menuToggle.addEventListener('click', (event) => {
        event.stopPropagation();

        const isOpen = !menuDropdown.hidden;

        // Ferme les menus des autres colonnes.
        document.querySelectorAll('.column-menu-dropdown').forEach((menu) => {
            menu.hidden = true;
        });

        document.querySelectorAll('.column-menu[aria-expanded="true"]').forEach((button) => {
            button.setAttribute('aria-expanded', 'false');
        });

        menuDropdown.hidden = isOpen;
        menuToggle.setAttribute('aria-expanded', String(!isOpen));
    });

    menuDropdown.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    document.addEventListener('click', (event) => {
        if (!column.contains(event.target)) {
            closeMenu();
        }
    });

    const enableTitleEditing = () => {
        if (saving || column.dataset.locked === 'true') return;

        titleInput.removeAttribute('readonly');
        titleInput.focus();
        titleInput.select();
    };

    editButton?.addEventListener('click', () => {
        closeMenu();
        enableTitleEditing();
    });

    // Double-clic sur le titre pour le modifier.
    titleInput.addEventListener('dblclick', enableTitleEditing);

    titleInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !titleInput.readOnly) {
            event.preventDefault();
            titleInput.blur();
        }

        if (event.key === 'Escape' && !titleInput.readOnly) {
            titleInput.value = originalName;
            titleInput.setAttribute('readonly', '');
            titleInput.blur();
        }
    });

    // Enregistre le titre en base de données.
    titleInput.addEventListener('blur', async () => {
        if (titleInput.readOnly || saving) return;

        const newName = titleInput.value.trim();
        titleInput.setAttribute('readonly', '');

        if (!newName) {
            titleInput.value = originalName;
            return;
        }

        if (newName === originalName) return;

        saving = true;

        const data = new URLSearchParams();
        data.set('name', newName);
        data.set('_token', titleInput.dataset.token);

        try {
            const response = await fetch(titleInput.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: data.toString(),
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Impossible de modifier le titre.');
            }

            titleInput.value = result.name;
            originalName = result.name;
        } catch (error) {
            titleInput.value = originalName;
            alert(error.message || 'Erreur lors de la modification du titre.');
        } finally {
            saving = false;
        }
    });

    // Verrouiller ou déverrouiller réellement la colonne.

    // ===== VERROUILLAGE DES COLONNES =====

    const isPermanentColumn = () =>
        titleInput.value.trim() === 'Done🎉';

    const applyPermanentLockStyle = () => {
        const isPermanentLocked =
            isPermanentColumn() &&
            column.dataset.locked === 'true';

        lockButton?.classList.toggle(
            'column-lock-permanent',
            isPermanentLocked
        );

        if (lockButton) {
            if (isPermanentLocked) {
                lockButton.style.setProperty('opacity', '0.45', 'important');
                lockButton.style.setProperty('filter', 'grayscale(1)', 'important');
                lockButton.style.setProperty('cursor', 'not-allowed', 'important');
                lockButton.title =
                    'Cette colonne ne peut pas être déverrouillée';
            } else {
                lockButton.style.removeProperty('opacity');
                lockButton.style.removeProperty('filter');
                lockButton.style.removeProperty('cursor');
                lockButton.title = '';
            }
        }
    };

    // Synchronise l'interface avec l'état du verrouillage.
    const updateLockUI = (isLocked) => {
        column.dataset.locked = String(isLocked);
        column.classList.toggle('column-locked-visual', isLocked);

        titleInput.setAttribute('readonly', '');

        if (editButton) {
            editButton.disabled = isLocked;
        }

        const deleteButton =
            column.querySelector('.column-delete-button');

        if (deleteButton) {
            deleteButton.disabled = isLocked;
        }

        if (lockButton) {
            lockButton.setAttribute(
                'aria-pressed',
                String(isLocked)
            );

            // Le bouton reste visible et conserve son libellé.
            if (lockLabel) {
                lockLabel.textContent = isLocked
                    ? 'Déverrouiller'
                    : 'Verrouiller';
            }

            // Ne pas désactiver le bouton : son clic est intercepté.
            lockButton.disabled = false;
            lockButton.removeAttribute('aria-label');
        }

        menuToggle.classList.toggle(
            'column-menu-locked',
            isLocked
        );

        applyPermanentLockStyle();
    };

    // Appliquer le style immédiatement au chargement de la page.
    applyPermanentLockStyle();

    // Intercepter le clic avant toute tentative de déverrouillage.
    lockButton?.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (lockButton.dataset.loading === 'true') {
            return;
        }

        const isTermine = isPermanentColumn();
        const previousLocked =
            column.dataset.locked === 'true';

        // Done🎉 est définitivement verrouillée.
        if (isTermine && previousLocked) {
            updateLockUI(true);
            return;
        }

        const nextLocked = !previousLocked;

        lockButton.dataset.loading = 'true';

        // Mise à jour visuelle immédiate.
        updateLockUI(nextLocked);

        const data = new URLSearchParams();
        data.set('_token', lockButton.dataset.token);

        try {
            const response = await fetch(lockButton.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type':
                        'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: data.toString()
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message ||
                    'Impossible de modifier le verrouillage.'
                );
            }

            // Appliquer l'état réellement enregistré côté serveur.
            updateLockUI(Boolean(result.isLocked));

            // Si le serveur confirme le verrouillage de Terminé,
            // appliquer immédiatement son style permanent.
            if (
                isPermanentColumn() &&
                column.dataset.locked === 'true'
            ) {
                updateLockUI(true);
            }

        } catch (error) {
            // Restaurer l'état précédent en cas d'échec.
            updateLockUI(previousLocked);

            console.error(
                'Erreur de verrouillage :',
                error
            );

            alert(
                error.message ||
                'Erreur lors du verrouillage.'
            );

        } finally {
            delete lockButton.dataset.loading;
        }
    });


});





/* ===== DRAG AND DROP NATIF DES COLONNES ===== */

document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('#board-columns');

    if (!container) return;

    // ===== PROTECTION DU DRAG & DROP DES CARTES =====

let pointerDownTarget = null;

// Mémoriser précisément l'élément sur lequel
// l'utilisateur commence son interaction.
document.addEventListener('pointerdown', event => {
    pointerDownTarget = event.target;
}, true);

// Réinitialiser après l'interaction.
document.addEventListener('pointerup', () => {
    pointerDownTarget = null;
}, true);

// Intercepter le drag natif AVANT les colonnes.
document.addEventListener('dragstart', event => {

    const origin = pointerDownTarget;

    if (!(origin instanceof Element)) return;

    // Si le glissement commence dans une zone de cartes,
    // interdire le drag natif de la colonne.
    if (origin.closest('.board-column-cards, .task-card')) {

        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

    }

}, true);

    let draggedColumn = null;
    let dragPreview = null;
    let originalOrder = [];
    let offsetX = 0;
    let offsetY = 0;
    let saving = false;

    const getColumns = () =>
        Array.from(container.querySelectorAll(':scope > .board-column'));

    // Toute la colonne devient saisissable.
    // Les contrôles interactifs gardent leur comportement normal.
    getColumns().forEach(column => {
        column.setAttribute('draggable', 'true');

column.addEventListener('dragstart', event => {

    const origin = pointerDownTarget;

    // Ne jamais déplacer une colonne lorsque le drag
    // commence dans une zone de cartes.
    if (
        origin instanceof Element &&
        origin.closest('.board-column-cards, .task-card')
    ) {
        event.preventDefault();
        event.stopPropagation();
        return;
    }

    // Ne pas démarrer depuis les contrôles interactifs.
    const interactive = origin instanceof Element
        ? origin.closest(
            'input, textarea, select, button, a, form, ' +
            '[contenteditable="true"], [draggable="true"]'
        )
        : null;

    if (interactive && interactive !== column) {
        event.preventDefault();
        return;
    }

    // Empêcher le déplacement des colonnes verrouillées.
    if (column.dataset.locked === 'true') {
        event.preventDefault();
        return;
    }

    draggedColumn = column;
    originalOrder = getColumns();

    const rect = column.getBoundingClientRect();

    offsetX = event.clientX - rect.left;
    offsetY = event.clientY - rect.top;

    dragPreview = column.cloneNode(true);
    dragPreview.classList.remove('dragging');
    dragPreview.classList.add('column-drag-preview');

    dragPreview.style.width = `${rect.width}px`;
    dragPreview.style.height = `${rect.height}px`;
    dragPreview.style.left = `${rect.left}px`;
    dragPreview.style.top = `${rect.top}px`;
    dragPreview.style.pointerEvents = 'none';

    document.body.appendChild(dragPreview);

    const emptyImage = new Image();

    emptyImage.src =
        'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';

    event.dataTransfer.setDragImage(emptyImage, 0, 0);
    event.dataTransfer.effectAllowed = 'move';

    event.dataTransfer.setData(
        'text/plain',
        column.dataset.id
    );

    requestAnimationFrame(() => {
        if (draggedColumn) {
            draggedColumn.classList.add('dragging');
        }
    });

});

        column.addEventListener('dragend', () => {
            column.classList.remove('dragging');

            dragPreview?.remove();
            dragPreview = null;

            container.querySelectorAll('.drag-over').forEach(item => {
                item.classList.remove('drag-over');
            });

            draggedColumn = null;
        });
    });

    // Déplace le clone visuel sous la souris.
    document.addEventListener('dragover', event => {
        if (!draggedColumn || !dragPreview) return;

        dragPreview.style.left = `${event.clientX - offsetX}px`;
        dragPreview.style.top = `${event.clientY - offsetY}px`;
    });

    // Réorganise les colonnes pendant le déplacement.
    container.addEventListener('dragover', event => {
        if (!draggedColumn) return;

        event.preventDefault();

        const target = event.target.closest('.board-column');

        if (!target || target === draggedColumn) return;

        // Une colonne verrouillée ne doit pas changer de position.
        if (target.dataset.locked === 'true') return;

        const rect = target.getBoundingClientRect();
        const insertAfter = event.clientX > rect.left + rect.width / 2;

        container.insertBefore(
            draggedColumn,
            insertAfter ? target.nextSibling : target
        );
    });

    // Enregistre le nouvel ordre.
    container.addEventListener('drop', async event => {
        if (!draggedColumn) return;

        event.preventDefault();

        if (saving) return;
        saving = true;

        const ids = getColumns().map(column => Number(column.dataset.id));

        try {
            const response = await fetch(container.dataset.reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    ids,
                    _token: container.dataset.token
                })
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Échec de la sauvegarde.'
                );
            }
        } catch (error) {
            originalOrder.forEach(column => {
                container.appendChild(column);
            });

            console.error('Erreur de sauvegarde :', error);

            alert(
                'Impossible d’enregistrer l’ordre des colonnes. ' +
                'L’ordre précédent a été restauré.'
            );
        } finally {
            saving = false;
        }
    });
});
















/* ===== CRÉATION DES CARTES EN BASE DE DONNÉES ===== */


/* ===== CRÉATION RAPIDE DES CARTES ===== */

document.querySelectorAll('.board-column').forEach((column) => {
    const addButton = column.querySelector('.add-card');
    const cardsContainer = column.querySelector('.board-column-cards');

    if (!addButton || !cardsContainer) return;

    addButton.addEventListener('click', async () => {
        if (addButton.disabled) {
            return;
        }

        addButton.disabled = true;

        // Créer la carte et son titre.
        const card = document.createElement('article');
        card.className = 'task-card task-card-pending';
        card.dataset.priority = 'low';

        /* Ligne contenant l'icône et le titre */
        const titleRow = document.createElement('div');
        titleRow.className = 'task-card-title-row';

        /* Icône de priorité */
        const priorityIcon = document.createElement('img');
        priorityIcon.className = 'task-card-priority';
        priorityIcon.src = addButton.dataset.priorityLowIcon;
        priorityIcon.alt = '';
        priorityIcon.title = 'Low';

        /* Titre */
        const title = document.createElement('div');
        title.className = 'task-card-title';
        title.textContent = 'Titre de carte';

        /* Assemblage */
        titleRow.appendChild(priorityIcon);
        titleRow.appendChild(title);

        // IMPORTANT : ajouter le bouton de modification dès la création.
        const detailsButton = document.createElement('button');
        detailsButton.type = 'button';
        detailsButton.className = 'task-card-details';
        detailsButton.setAttribute('aria-label', 'Voir les détails');

        const editIcon = document.createElement('img');
        editIcon.src = addButton.dataset.editIcon;
        editIcon.alt = '';
        editIcon.onerror = () => {
            console.error('Impossible de charger l’icône de modification.');
        };

        detailsButton.appendChild(editIcon);

        card.appendChild(titleRow);
        card.appendChild(detailsButton);

        // Afficher la carte immédiatement.
        cardsContainer.appendChild(card);

        const data = new URLSearchParams();
        data.set('_token', addButton.dataset.token);

        try {
            const response = await fetch(addButton.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: data.toString()
            });

            const result = await response.json();

            if (!response.ok || !result.success || !result.card?.id) {
                throw new Error(
                    result.message || 'Impossible de créer la carte.'
                );
            }

            // Associer la carte à son identifiant en base de données.
            card.dataset.id = result.card.id;
            title.textContent = result.card.title;
            card.classList.remove('task-card-pending');

        } catch (error) {
            card.remove();

            console.error('Erreur de création :', error);
            alert(error.message || 'Erreur lors de la création de la carte.');

        } finally {
            addButton.disabled = false;
        }
    });
});









/* ===== DRAG & DROP DES CARTES ===== */
/* ===== DRAG & DROP DES CARTES ===== */

document.addEventListener('DOMContentLoaded', () => {
    const board = document.querySelector('#board-columns');

    if (!board || typeof Sortable === 'undefined') return;

    const getColumn = element => element.closest('.board-column');

    const getCardIds = container =>
        Array.from(container.querySelectorAll(':scope > .task-card'))
            .map(card => Number(card.dataset.id))
            .filter(id => Number.isInteger(id) && id > 0);

    const containers = board.querySelectorAll('.board-column-cards');

    let activeDropZone = null;

    const launchConfetti = (column) => {
        const title = column.querySelector('.column-title-input');

        if (!title) return;

        const rect = title.getBoundingClientRect();

        const x = (rect.left + rect.width / 2) / window.innerWidth;
        const y = (rect.top + rect.height / 2) / window.innerHeight;

        confetti({
            particleCount: 180,
            spread: 100,
            startVelocity: 45,
            scalar: 0.4,
            origin: { x, y },
            colors: ['#FFD700', '#FF5C8A', '#5BC0EB', '#9B5DE5', '#00BB77'],
            disableForReducedMotion: true
        });
    };

    const clearDropZones = () => {
        containers.forEach(container => {
            container.classList.remove(
                'task-drop-zone',
                'drop-zone-active'
            );
        });

        activeDropZone = null;
    };

    containers.forEach(cardsContainer => {

        new Sortable(cardsContainer, {

            group: {
                name: 'kanban-cards',
                pull: true,
                put: true
            },

            draggable: '.task-card',

            animation: 180,

            ghostClass: 'task-card-ghost',
            chosenClass: 'task-card-chosen',
            dragClass: 'task-card-dragging',

            forceFallback: true,
            fallbackOnBody: true,
            fallbackClass: 'task-card-fallback',

            onStart() {

                // Afficher toutes les zones de dépôt.
                containers.forEach(container => {
                    container.classList.add('task-drop-zone');
                });

            },

            onMove(event) {

                const source = getColumn(event.from);
                const target = getColumn(event.to);

                if (!source || !target) return false;

                // Mettre en évidence la zone survolée.
                if (activeDropZone !== event.to) {

                    activeDropZone?.classList.remove(
                        'drop-zone-active'
                    );

                    activeDropZone = event.to;

                    activeDropZone.classList.add(
                        'drop-zone-active'
                    );
                }

                return true;
            },

            async onEnd(event) {

                clearDropZones();

                const card = event.item;

                const sourceColumn = getColumn(event.from);
                const targetColumn = getColumn(event.to);

                if (!sourceColumn || !targetColumn) return;

                const targetColumnTitle = targetColumn
                    .querySelector('.column-title-input')
                    ?.value.trim();

                const arrivedInDone =
                    sourceColumn !== targetColumn &&
                    targetColumnTitle === 'Done🎉';

                if (
                    event.from === event.to &&
                    event.oldIndex === event.newIndex
                ) {
                    return;
                }

                const sourceCards =
                    sourceColumn.querySelector('.board-column-cards');

                const targetCards =
                    targetColumn.querySelector('.board-column-cards');

                const payload = {
                    targetColumnId: Number(targetColumn.dataset.id),

                    sourceCardIds: getCardIds(sourceCards),

                    targetCardIds: getCardIds(targetCards),

                    _token: board.dataset.cardMoveToken
                };

                card.classList.add('task-card-saving');

                try {

                    const url = board.dataset.cardMoveUrlTemplate.replace(
                        /\/0\/move$/,
                        `/${card.dataset.id}/move`
                    );

                    const response = await fetch(url, {
                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },

                        credentials: 'same-origin',

                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(
                            result.message ||
                            'Impossible d’enregistrer le déplacement.'
                        );
                    }
                    if (arrivedInDone) {
                        launchConfetti(targetColumn);
                    }

                } catch (error) {

                    console.error('Erreur de déplacement :', error);

                    alert(error.message);

                    window.location.reload();

                } finally {

                    card.classList.remove('task-card-saving');

                    clearDropZones();
                }
            }

        });

    });
















    

/* ===== MODALE DE MODIFICATION DES CARTES ===== */
console.log('INITIALISATION MODALE OK');

const modal = document.querySelector('#edit-card-modal');
const form = document.querySelector('#edit-card-form');

if (!modal || !form) {
    console.error('Modale ou formulaire introuvable');
    return;
}

console.log('MODALE ET FORMULAIRE TROUVÉS');

const titleInput = document.querySelector('#edit-card-title');
const descriptionInput = document.querySelector('#edit-card-description');
const membersSelect = document.querySelector('#edit-card-members');
const prioritySelect = document.querySelector('#edit-card-priority');
const dueDateInput = document.querySelector('#edit-card-due-date');
const errorMessage = document.querySelector('#edit-card-error');
const saveButton = document.querySelector('#edit-card-save');

/* ===== SÉLECTEUR PERSONNALISÉ DES PRIORITÉS ===== */

const priorityWidget = document.querySelector('#priority-select');

if (!priorityWidget || !prioritySelect) {
    console.error('Sélecteur de priorité introuvable');
    return;
}

const priorityButton = priorityWidget.querySelector('.priority-select-button');
const priorityOptions = priorityWidget.querySelector('.priority-select-options');
const prioritySelectedIcon = priorityWidget.querySelector('.priority-selected-icon');
const prioritySelectedLabel = priorityWidget.querySelector('.priority-selected-label');

const priorityIcons = {
    low: '/icons/icon-priority-Low.png',
    medium: '/icons/icon-priority-Medium.png',
    high: '/icons/icon-priority-High.png',
    highest: '/icons/icon-priority-Highest.png'
};

const priorityLabels = {
    low: 'Low',
    medium: 'Medium',
    high: 'High',
    highest: 'Highest'
};

const updatePriorityDisplay = (priority) => {
    const value = priorityLabels[priority] ? priority : 'low';

    prioritySelect.value = value;

    prioritySelectedIcon.src = priorityIcons[value];
    prioritySelectedIcon.alt = '';

    prioritySelectedLabel.textContent = priorityLabels[value];

    priorityOptions.querySelectorAll('[data-priority]').forEach(option => {
        const selected = option.dataset.priority === value;

        option.classList.toggle('selected', selected);
        option.setAttribute('aria-selected', String(selected));
    });
};

/* Ouvrir et fermer la liste des priorités */
priorityButton.addEventListener('click', () => {
    const isOpen = !priorityOptions.hidden;

    priorityOptions.hidden = isOpen;
    priorityButton.setAttribute('aria-expanded', String(!isOpen));
});

/* Choisir une priorité */
priorityOptions.querySelectorAll('[data-priority]').forEach(option => {
    option.addEventListener('click', () => {
        updatePriorityDisplay(option.dataset.priority);

        priorityOptions.hidden = true;
        priorityButton.setAttribute('aria-expanded', 'false');
    });
});

/* Fermer la liste en cliquant ailleurs */
document.addEventListener('click', event => {
    if (!priorityWidget.contains(event.target)) {
        priorityOptions.hidden = true;
        priorityButton.setAttribute('aria-expanded', 'false');
    }
});

/* Valeur par défaut */
updatePriorityDisplay('low');

/* ===== VARIABLES DE LA MODALE ===== */

let currentCard = null;
let currentToken = null;
let saving = false;

const showError = (message) => {
    errorMessage.textContent = message;
    errorMessage.hidden = false;
};

const closeModal = () => {
    if (saving) return;

    modal.hidden = true;
    currentCard = null;
    currentToken = null;
};

modal.querySelectorAll('[data-close-edit-modal]').forEach(button => {
    button.addEventListener('click', closeModal);
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !modal.hidden) {
        closeModal();
    }
});

/* ===== OUVERTURE DE LA MODALE AU CLIC SUR L'ICÔNE ===== */

/* ===== OUVERTURE RAPIDE DE LA MODALE ===== */

let loadingCard = false;

document.addEventListener('click', async event => {
    const detailsButton = event.target.closest('.task-card-details');

    if (!detailsButton) return;

    event.preventDefault();
    event.stopPropagation();

    const cardElement = detailsButton.closest('.task-card');

    if (!cardElement || !cardElement.dataset.id) return;

    const column = cardElement.closest('.board-column');

    if (!column || saving || loadingCard) return;

    currentCard = cardElement;
    currentToken = null;
    loadingCard = true;

    // Afficher immédiatement la modale.
    errorMessage.hidden = true;
    errorMessage.textContent = '';

    modal.hidden = false;
    modal.setAttribute('aria-busy', 'true');

    // Afficher un état de chargement.
    document.querySelector('#edit-card-heading').textContent =
        'Chargement de la tâche...';

    titleInput.value = '';
    descriptionInput.value = '';
    membersSelect.replaceChildren();

    // Désactiver temporairement les champs.
    const fields = form.querySelectorAll(
        'input, textarea, select, button[type="submit"]'
    );

    fields.forEach(field => {
        field.disabled = true;
    });

    try {
        const response = await fetch(
            `/board/card/${encodeURIComponent(cardElement.dataset.id)}/edit`,
            {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || 'Impossible de charger cette tâche.'
            );
        }

        // Si la modale a été fermée pendant le chargement,
        // ne pas modifier son contenu.
        if (currentCard !== cardElement) return;

        currentToken = result.token;

        titleInput.value = result.card.title ?? '';
        descriptionInput.value = result.card.description ?? '';
        updatePriorityDisplay(result.card.priority ?? 'low');
        dueDateInput.value = result.card.dueDate ?? '';

        // Charger les membres disponibles.
        const assignedIds = (
            result.card.assignedMembers ?? []
        ).map(String);

        membersSelect.replaceChildren();

        for (const member of result.members ?? []) {
            const option = document.createElement('option');

            option.value = String(member.id);
            option.textContent = member.name;
            option.selected = assignedIds.includes(option.value);

            membersSelect.appendChild(option);
        }

        document.querySelector('#edit-card-heading').textContent =
            'Modify the task';

        titleInput.focus();

    } catch (error) {
        if (currentCard === cardElement) {
            showError(
                error.message || 'Erreur lors du chargement de la tâche.'
            );
        }

    } finally {
        // Réactiver les champs.
        fields.forEach(field => {
            field.disabled = false;
        });

        modal.removeAttribute('aria-busy');
        loadingCard = false;
    }
});


/* ===== ENREGISTREMENT DES MODIFICATIONS ===== */
form.addEventListener('submit', async event => {
    event.preventDefault();

    if (!currentCard || !currentToken || saving) return;

    errorMessage.hidden = true;

    const payload = {
        _token: currentToken,
        title: titleInput.value.trim(),
        description: descriptionInput.value,
        priority: prioritySelect.value,
        dueDate: dueDateInput.value || null,
        assignedMembers: Array.from(membersSelect.selectedOptions)
            .map(option => Number(option.value))
    };

    saving = true;
    saveButton.disabled = true;

    try {
        const response = await fetch(
            `/board/card/${encodeURIComponent(currentCard.dataset.id)}/update`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message ||
                'Impossible d’enregistrer les modifications.'
            );
        }

        // Mettre à jour le titre visible sur la carte.
        const cardTitle = currentCard.querySelector('.task-card-title');

        if (cardTitle) {
            cardTitle.textContent = result.card.title;
        }

        modal.hidden = true;
        currentCard = null;
        currentToken = null;

    } catch (error) {
        showError(
            error.message || 'Erreur lors de l’enregistrement.'
        );
    } finally {
        saving = false;
        saveButton.disabled = false;
    }
});

});