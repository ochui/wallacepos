function WPOS_Installer() {
  this.loadInstallerStep = function (query) {
    var contenturl;

    contenturl = "api/installer/content/" + sec + "";

    $.get(
      contenturl,
      query,
      function (data) {
        $("#maincontent").html(data);
      },
      "html"
    );
  };
}
