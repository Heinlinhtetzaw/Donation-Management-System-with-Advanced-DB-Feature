document.querySelectorAll('.read-more-btn').forEach(button => {
    button.addEventListener('click', () => {
        const card = button.closest('.news-card');
        const content = card.querySelector('.read-more-content');
        const buttons = card.querySelector('.buttons');

        if (content.style.maxHeight) {
            // Collapse the content
            content.style.maxHeight = null;
            button.textContent = 'Read More';
        } else {
            // Expand the content
            content.style.maxHeight = content.scrollHeight + 'px';
            button.textContent = 'Read Less';
        }
    });
});