function eduvidual_init(data) {
  if (!data.isloggedin && $('body').attr('id') == 'page-site-index') {
    // login nach header verschieben
    $('.block.block_login')
      .insertBefore('.hero-section')
      .addClass('special-startpage-login-box');
  }

  if (data.orgmenu) {
    $(data.orgmenu).insertBefore('.usermenu-container');
  }


  document.querySelectorAll(".nav-item a[href*='/local/eduvidual/pages/redirects/edutube.php']").forEach(function(element) {
    element.setAttribute('target', '_blank');
  });
}
