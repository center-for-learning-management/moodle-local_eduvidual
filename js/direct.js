function eduvidual_init(data) {
  if (!data.isloggedin && $('body').attr('id') == 'page-site-index') {
    // login nach header verschieben
    $('.block.block_login')
      .insertBefore('.hero-section')
      .addClass('special-startpage-login-box');
  }

  document.querySelectorAll(".nav-item a[href*='/local/eduvidual/pages/redirects/edutube.php']").forEach(function(element) {
    element.setAttribute('target', '_blank');
  });
}
