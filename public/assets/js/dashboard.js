(function () {
            var doc = document.documentElement;
            var key = 'steam-dashboard-theme';
            var saved = localStorage.getItem(key);

            if (saved === 'dark') {
                doc.setAttribute('data-theme', 'dark');
            }

            var toggle = document.getElementById('theme-toggle');
            if (toggle) {
                toggle.addEventListener('click', function () {
                    var isDark = doc.getAttribute('data-theme') === 'dark';
                    if (isDark) {
                        doc.removeAttribute('data-theme');
                        localStorage.setItem(key, 'light');
                    } else {
                        doc.setAttribute('data-theme', 'dark');
                        localStorage.setItem(key, 'dark');
                    }
                });
            }

            var copyButton = document.getElementById('copy-link');
            if (copyButton) {
                copyButton.addEventListener('click', function () {
                    var relativeUrl = copyButton.getAttribute('data-share-url') || '';
                    var fullUrl = window.location.origin + relativeUrl;

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(fullUrl).then(function () {
                            copyButton.textContent = 'URL copiada';
                            setTimeout(function () {
                                copyButton.textContent = 'Copiar URL dos filtros';
                            }, 1400);
                        });
                    } else {
                        window.prompt('Copie o link:', fullUrl);
                    }
                });
            }
        })();
