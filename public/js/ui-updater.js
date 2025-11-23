/**
 * Displays a non-blocking notification message at the top of the screen.
 * @param {string} message - The message to display.
 * @param {string} type - 'info', 'success', or 'error'.
 */
export function showNotification(message, type = 'info') {
    const container = document.getElementById('notification-container');
    if (!container) {
        console.error('Notification container not found.');
        return;
    }

    const notification = document.createElement('div');
    let bgColor, textColor;

    switch (type) {
        case 'success':
            bgColor = 'bg-green-500';
            textColor = 'text-white';
            break;
        case 'error':
            bgColor = 'bg-red-500';
            textColor = 'text-white';
            break;
        default:
            bgColor = 'bg-blue-500';
            textColor = 'text-white';
    }

    notification.className = `p-4 rounded-md shadow-lg ${bgColor} ${textColor} text-center transition-transform transform translate-y-[-100px]`;
    notification.textContent = message;
    
    container.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateY(0)';
    }, 100);

    // Animate out and remove
    setTimeout(() => {
        notification.style.transform = 'translateY(0)';
        notification.addEventListener('transitionend', () => {
            notification.remove();
        });
    }, 3000);
}


/**
 * Displays a confirmation modal and returns a promise that resolves with the user's choice.
 * @param {string} message - The confirmation message to display.
 * @returns {Promise<boolean>} - A promise that resolves to true if 'Confirm' is clicked, false otherwise.
 */
export function showConfirmation(message) {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmation-modal');
        const messageEl = document.getElementById('confirmation-message');
        const confirmBtn = document.getElementById('confirmation-confirm-btn');
        const cancelBtn = document.getElementById('confirmation-cancel-btn');

        if (!modal || !messageEl || !confirmBtn || !cancelBtn) {
            console.error('Confirmation modal elements not found.');
            resolve(false); // Can't show confirmation, resolve as false
            return;
        }

        messageEl.textContent = message;
        modal.classList.remove('hidden');

        const handleConfirm = () => {
            modal.classList.add('hidden');
            cleanup();
            resolve(true);
        };

        const handleCancel = () => {
            modal.classList.add('hidden');
            cleanup();
            resolve(false);
        };

        const cleanup = () => {
            confirmBtn.removeEventListener('click', handleConfirm);
            cancelBtn.removeEventListener('click', handleCancel);
        };

        confirmBtn.addEventListener('click', handleConfirm);
        cancelBtn.addEventListener('click', handleCancel);
    });
}

