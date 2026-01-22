import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.min.css';

document.addEventListener('DOMContentLoaded', function() {
    // Initialize GLightbox only if needed
    let glightbox = GLightbox({
        selector: '[data-gallery="product-gallery"]'
    });

    // Main gallery logic
    const mainImage = document.getElementById('main-image');
    const mainLink = document.getElementById('main-image-link');
    const zoomBtn  = document.getElementById('zoom-btn');
    const thumbs   = document.querySelectorAll('[data-thumb-index]');
    if (!mainImage || !mainLink || !zoomBtn || !thumbs.length) {
        // Gallery not present, skip
        return;
    }

    let images = [];
    thumbs.forEach(thumb => images.push(thumb.querySelector('img').src));
    let current = 0;

    thumbs.forEach((thumb, idx) => {
        thumb.addEventListener('click', function() {
            // Change main image and link
            mainImage.src = images[idx];
            mainLink.href = images[idx];
            // Highlight
            thumbs.forEach(t => t.classList.remove('ring-2', 'ring-gray-300'));
            thumb.classList.add('ring-2', 'ring-gray-300');
            current = idx;
        });
    });

    // Open lightbox at correct image
    function openLightbox() {
        let links = Array.from(document.querySelectorAll('[data-gallery="product-gallery"]'));
        if (links[current]) links[current].click();
    }

    zoomBtn.addEventListener('click', function(e) {
        e.preventDefault();
        openLightbox();
    });

    mainLink.addEventListener('click', function(e) {
        e.preventDefault();
        openLightbox();
    });
});
