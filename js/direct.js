window.onload = function () {
  document.querySelectorAll("#nav-drawer a[href*='/local/eduvidual/pages/redirects/edutube.php']").forEach((element) => {
    element.setAttribute('target', '_blank');
  });
};

function eduvidual_init(data) {
  if (!data.isloggedin && $('body').attr('id') == 'page-site-index') {
    // login nach header verschieben
    $('.block.block_login')
      .insertBefore('.hero-section')
      .addClass('special-startpage-login-box');
  }
}
