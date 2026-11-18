
window.addEventListener('load', () => {
    const loader = document.getElementById('loadingScreen');

    setTimeout(() => {
        if (loader) {
            loader.classList.add('hidden');

            setTimeout(() => {
                loader.remove();
            }, 5000);
        }
    }, 4500);
}); 