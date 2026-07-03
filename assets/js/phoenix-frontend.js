(function () {
    function setStatus(nextStatus) {
        var node = document.querySelector('[data-phoenix-status]');
        if (!node) {
            return;
        }
        node.textContent = nextStatus;
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        if (target.closest('[data-phoenix-start]')) {
            setStatus('Actief');
        }

        if (target.closest('[data-phoenix-stop]')) {
            setStatus('Gestopt');
        }
    });
})();
