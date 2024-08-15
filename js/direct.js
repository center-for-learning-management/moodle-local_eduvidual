function eduvidual_init(data) {
  // ACHTUNG: kein jquery verfügbar!

  // if (!data.isloggedin && $('body').attr('id') == 'page-site-index') {
  //   // login nach header verschieben
  //   $('.block.block_login')
  //     .insertBefore('.hero-section')
  //     .addClass('special-startpage-login-box');
  // }

  // if (data.orgmenu) {
  //   $(data.orgmenu).insertBefore('.usermenu-container');
  // }
  if (data.orgmenu) {
    var orgMenu = document.createRange().createContextualFragment(data.orgmenu);
    var userMenuContainer = document.querySelector('.usermenu-container');

    if (userMenuContainer) {
      userMenuContainer.parentNode.insertBefore(orgMenu, userMenuContainer);
    }
  }

  document.querySelectorAll(".nav-item a[href*='/local/eduvidual/pages/redirects/edutube.php']").forEach(function(element) {
    element.setAttribute('target', '_blank');
  });
}
