window.onload = function () {
    document.querySelectorAll("#nav-drawer a[href*='/local/eduvidual/pages/redirects/edutube.php']").forEach((element) => {
        element.setAttribute('target', '_blank');
    });
}
