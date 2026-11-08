// If a page is loaded and the backend says "not logged in", bounce to login.
(function () {
  fetch('api/index.php?action=profile.get', { credentials: 'same-origin' })
    .then(r => {
      if (r.status === 401) location.href = 'index.php';
    })
    .catch(() => {});
})();