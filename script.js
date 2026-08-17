/**
 * ClubManager Events Dashboard JavaScript
 * Implements accessible modal toggles, focus trapping, ESC key listeners, and backdrop click handlers.
 */

document.addEventListener('DOMContentLoaded', function () {
    // DOM Selectors
    const modalOverlay = document.getElementById('eventModalOverlay');
    const modalContent = modalOverlay ? modalOverlay.querySelector('.modal-content') : null;
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalCancelBtn = document.getElementById('modalCancelBtn');
    const openModalBtns = document.querySelectorAll('.btn-open-modal');
    const createEventForm = document.getElementById('createEventForm');
    const eventTitleInput = document.getElementById('eventTitle');

    let previousActiveElement = null;

    /**
     * Opens the Create Event modal and sets focus
     */
    function openModal(event) {
        if (event) {
            event.preventDefault();
        }
        previousActiveElement = document.activeElement;

        if (modalOverlay) {
            modalOverlay.classList.add('active');
            modalOverlay.setAttribute('aria-hidden', 'false');

            // Lock background scrolling
            document.body.style.overflow = 'hidden';

            // Set focus to the first input field
            setTimeout(function () {
                if (eventTitleInput) {
                    eventTitleInput.focus();
                } else if (modalContent) {
                    modalContent.focus();
                }
            }, 50);
        }
    }

    /**
     * Closes the Create Event modal and restores focus
     */
    function closeModal() {
        if (modalOverlay && modalOverlay.classList.contains('active')) {
            modalOverlay.classList.remove('active');
            modalOverlay.setAttribute('aria-hidden', 'true');

            // Restore body scroll
            document.body.style.overflow = '';

            // Return focus to trigger element
            if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
                previousActiveElement.focus();
            }
        }
    }

    // Attach click listeners to all open modal trigger buttons
    openModalBtns.forEach(function (btn) {
        btn.addEventListener('click', openModal);
    });

    // Close buttons
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }
    if (modalCancelBtn) {
        modalCancelBtn.addEventListener('click', closeModal);
    }

    // Close on backdrop overlay click
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function (e) {
            if (e.target === modalOverlay) {
                closeModal();
            }
        });
    }

    // Keyboard Navigation: Escape key close & Tab focus trap
    document.addEventListener('keydown', function (e) {
        if (!modalOverlay || !modalOverlay.classList.contains('active')) {
            return;
        }

        // Escape Key
        if (e.key === 'Escape' || e.key === 'Esc') {
            closeModal();
            return;
        }

        // Tab Focus Trapping
        if (e.key === 'Tab') {
            const focusableElements = modalOverlay.querySelectorAll(
                'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );

            if (focusableElements.length === 0) return;

            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (e.shiftKey) { // Shift + Tab
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else { // Tab
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        }
    });

    // Form submission handling
    if (createEventForm) {
        createEventForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const title = document.getElementById('eventTitle') ? document.getElementById('eventTitle').value : '';
            const dateTime = document.getElementById('eventDateTime') ? document.getElementById('eventDateTime').value : '';
            const description = document.getElementById('eventDescription') ? document.getElementById('eventDescription').value : '';

            if (!title || !dateTime || !description) {
                alert('Please complete all required fields.');
                return;
            }

            closeModal();
            createEventForm.reset();
            alert('Event "' + title + '" created successfully!');
        });
    }
});
