document.querySelectorAll('.read-more-btn').forEach(function (button) {
    button.addEventListener('click', function () {
        const detailsId = button.getAttribute('aria-controls');
        const details = document.getElementById(detailsId);
        if (!details) {
            return;
        }

        const isExpanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        button.textContent = isExpanded ? 'Read More' : 'Read Less';
        details.style.maxHeight = isExpanded ? '0px' : details.scrollHeight + 'px';
    });
});
