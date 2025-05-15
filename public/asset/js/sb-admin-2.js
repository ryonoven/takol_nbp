(function ($) {
  "use strict";

  // Toggle the side navigation
  $("#sidebarToggle, #sidebarToggleTop").on("click", function (e) {
    $("body").toggleClass("sidebar-toggled");
    $(".sidebar").toggleClass("toggled");
    if ($(".sidebar").hasClass("toggled")) {
      $(".sidebar .collapse").collapse("hide");
    }
  });

  // Close any open menu accordions when window is resized below 768px
  $(window).resize(function () {
    if ($(window).width() < 768) {
      $(".sidebar .collapse").collapse("hide");
    }

    // Toggle the side navigation when window is resized below 480px
    if ($(window).width() < 480 && !$(".sidebar").hasClass("toggled")) {
      $("body").addClass("sidebar-toggled");
      $(".sidebar").addClass("toggled");
      $(".sidebar .collapse").collapse("hide");
    }
  });

  // Prevent the content wrapper from scrolling when the fixed side navigation hovered over
  $("body.fixed-nav .sidebar").on(
    "mousewheel DOMMouseScroll wheel",
    function (e) {
      if ($(window).width() > 768) {
        var e0 = e.originalEvent,
          delta = e0.wheelDelta || -e0.detail;
        this.scrollTop += (delta < 0 ? 1 : -1) * 30;
        e.preventDefault();
      }
    }
  );

  // Scroll to top button appear
  $(document).on("scroll", function () {
    var scrollDistance = $(this).scrollTop();
    if (scrollDistance > 100) {
      $(".scroll-to-top").fadeIn();
    } else {
      $(".scroll-to-top").fadeOut();
    }
  });

  // Smooth scrolling using jQuery easing
  $(document).on("click", "a.scroll-to-top", function (e) {
    var $anchor = $(this);
    $("html, body")
      .stop()
      .animate(
        {
          scrollTop: $($anchor.attr("href")).offset().top,
        },
        1000,
        "easeInOutExpo"
      );
    e.preventDefault();
  });

  // Modal handling
  var idDataHapus;
  $("button#btn-hapus").click(function () {
    idDataHapus = $(this).data("id");
    console.log(idDataHapus);
    $("#idData").text(idDataHapus);
    $("#modalHapus").modal("show");
  });

  $("#btnHapus").click(function () {
    window.location.href = "bisnis/hapus/" + idDataHapus;
  });

  var idDataHapus2;
  $("button#btn-hapus").click(function () {
    idDataHapus2 = $(this).data("id");
    console.log(idDataHapus2);
    $("#idDataI").text(idDataHapus2);
    $("#modalHapusI").modal("show");
  });

  $("#btnHapusI").click(function () {
    window.location.href = "inv/hapus/" + idDataHapus2;
  });

  var idDataHapus;
  $("button#btn-hapus").click(function () {
    idDataHapus = $(this).data("id");
    console.log(idDataHapus);
    $("#idDatapenjelas").text(idDataHapus);
    $("#modalHapuspenjelas").modal("show");
  });
  $("#btnHapuspenjelas").click(function () {
    window.location.href = "penjelasanumum/hapus/" + idDataHapus;
  });

  var idDataHapus3;
  $("button#btn-hapus").click(function () {
    idDataHapus3 = $(this).data("id");
    console.log(idDataHapus3);
    $("#idDatadir").text(idDataHapus3);
    $("#modalHapusdir").modal("show");
  });

  $("#btnHapusdir").click(function () {
    window.location.href = "tgjwbdir/hapus/" + idDataHapus3;
  });

  var idDataHapus4;
  $("button#btn-hapus").click(function () {
    idDataHapus4 = $(this).data("id");
    console.log(idDataHapus4);
    $("#idDatadekom").text(idDataHapus4);
    $("#modalHapusdekom").modal("show");
  });

  $("#btnHapusdekom").click(function () {
    window.location.href = "tgjwbdekom/hapus/" + idDataHapus4;
  });

  var idDataHapus5;
  $("button#btn-hapus").click(function () {
    idDataHapus5 = $(this).data("id");
    console.log(idDataHapus5);
    $("#idDatakomite").text(idDataHapus5);
    $("#modalHapuskomite").modal("show");
  });

  $("#btnHapuskomite").click(function () {
    window.location.href = "tgjwbkomite/hapus/" + idDataHapus5;
  });

  var idDataHapus6;
  $("button#btn-hapus").click(function () {
    idDataHapus6 = $(this).data("id");
    console.log(idDataHapus6);
    $("#idDatastrukturkomite").text(idDataHapus6);
    $("#modalHapusstrukturkomite").modal("show");
  });

  $("#btnHapusstrukturkomite").click(function () {
    window.location.href = "strukturkomite/hapus/" + idDataHapus6;
  });

  var idDataHapus7;
  $("button#btn-hapus").click(function () {
    idDataHapus7 = $(this).data("id");
    console.log(idDataHapus7);
    $("#idDatasahamdirdekom").text(idDataHapus7);
    $("#modalHapussahamdirdekom").modal("show");
  });

  $("#btnHapussahamdirdekom").click(function () {
    window.location.href = "sahamdirdekom/hapus/" + idDataHapus7;
  });

  var idDataHapus8;
  $("button#btn-hapus").click(function () {
    idDataHapus8 = $(this).data("id");
    console.log(idDataHapus8);
    $("#idDatashmusahadirdekom").text(idDataHapus8);
    $("#modalHapusshmusahadirdekom").modal("show");
  });

  $("#btnHapusshmusahadirdekom").click(function () {
    window.location.href = "shmusahadirdekom/hapus/" + idDataHapus8;
  });

  var idDataHapus9;
  $("button#btn-hapus").click(function () {
    idDataHapus9 = $(this).data("id");
    console.log(idDataHapus9);
    $("#idDatashmdirdekomlain").text(idDataHapus9);
    $("#modalHapusshmdirdekomlain").modal("show");
  });

  $("#btnHapusshmdirdekomlain").click(function () {
    window.location.href = "shmdirdekomlain/hapus/" + idDataHapus9;
  });

  var idDataHapus10;
  $("button#btn-hapus").click(function () {
    idDataHapus10 = $(this).data("id");
    console.log(idDataHapus10);
    $("#idDatakeuangandirdekompshm").text(idDataHapus10);
    $("#modalHapuskeuangandirdekompshm").modal("show");
  });

  $("#btnHapuskeuangandirdekompshm").click(function () {
    window.location.href = "keuangandirdekompshm/hapus/" + idDataHapus10;
  });

  var idDataHapus11;
  $("button#btn-hapus").click(function () {
    idDataHapus11 = $(this).data("id");
    console.log(idDataHapus11);
    $("#idDatakeluargadirdekompshm").text(idDataHapus11);
    $("#modalHapuskeluargadirdekompshm").modal("show");
  });

  $("#btnHapuskeluargadirdekompshm").click(function () {
    window.location.href = "keluargadirdekompshm/hapus/" + idDataHapus11;
  });

  var idDataHapus12;
  $("button#btn-hapus").click(function () {
    idDataHapus12 = $(this).data("id");
    console.log(idDataHapus12);
    $("#idDatapaketkebijakandirdekom").text(idDataHapus12);
    $("#modalHapuspaketkebijakandirdekom").modal("show");
  });

  $("#btnHapuspaketkebijakandirdekom").click(function () {
    window.location.href = "paketkebijakandirdekom/hapus/" + idDataHapus12;
  });

  var idDataHapus13;
  $("button#btn-hapus").click(function () {
    idDataHapus13 = $(this).data("id");
    console.log(idDataHapus13);
    $("#idDatarasio").text(idDataHapus13);
    $("#modalHapusrasio").modal("show");
  });

  $("#btnHapusrasio").click(function () {
    window.location.href = "rasiogaji/hapus/" + idDataHapus13;
  });

  var idDataHapus14;
  $("button#btn-hapus").click(function () {
    idDataHapus14 = $(this).data("id");
    console.log(idDataHapus14);
    $("#idDatakehadirandekom").text(idDataHapus14);
    $("#modalHapuskehadirandekom").modal("show");
  });

  $("#btnHapuskehadirandekom").click(function () {
    window.location.href = "kehadirandekom/hapus/" + idDataHapus14;
  });

  var idDataHapus16;
  $("button#btn-hapus").click(function () {
    idDataHapus16 = $(this).data("id");
    console.log(idDataHapus16);
    $("#idDatarapat").text(idDataHapus16);
    $("#modalHapusrapat").modal("show");
  });

  $("#btnHapusrapat").click(function () {
    window.location.href = "rapat/hapus/" + idDataHapus16;
  });

  var idDataHapus17a;
  $("button#btn-hapus").click(function () {
    idDataHapus17a = $(this).data("id");
    console.log(idDataHapus17a);
    $("#idDatafraudinternal").text(idDataHapus17a);
    $("#modalHapusfraudinternal").modal("show");
  });

  $("#btnHapusfraudinternal").click(function () {
    window.location.href = "fraudinternal/hapus/" + idDataHapus17a;
  });

  var idDataHapus18;
  $("button#btn-hapus").click(function () {
    idDataHapus18 = $(this).data("id");
    console.log(idDataHapus18);
    $("#idDatamasalahhukum").text(idDataHapus18);
    $("#modalHapusmasalahhukum").modal("show");
  });

  $("#btnHapusmasalahhukum").click(function () {
    window.location.href = "masalahhukum/hapus/" + idDataHapus18;
  });

  var idDataHapus19;
  $("button#btn-hapus").click(function () {
    idDataHapus19 = $(this).data("id");
    console.log(idDataHapus19);
    $("#idDatatransaksikepentingan").text(idDataHapus19);
    $("#modalHapustransaksikepentingan").modal("show");
  });

  $("#btnHapustransaksikepentingan").click(function () {
    window.location.href = "transaksikepentingan/hapus/" + idDataHapus19;
  });

  var idDataHapus20;
  $("button#btn-hapus").click(function () {
    idDataHapus20 = $(this).data("id");
    console.log(idDataHapus20);
    $("#idDatadanasosisal").text(idDataHapus20);
    $("#modalHapusdanasosial").modal("show");
  });

  $("#btnHapusdanasosial").click(function () {
    window.location.href = "danasosial/hapus/" + idDataHapus20;
  });

  // //Hide and show data collapse
  // $(document).ready(function() {
  //   $('.collapse-link').click(function(e) {
  //       e.preventDefault(); // Mencegah perilaku default link

  //       var target = $(this).attr('#collapseFaktor');
  //       var target = $(this).attr('#collapseFaktor2'); // Mendapatkan target collapse

  //       $('.collapse-item').each(function() {
  //           if ($(this).attr('id') !== target.substring(1)) {
  //               $(this).collapse('hide'); // Menutup semua collapse kecuali target
  //           }
  //       });

  //       $(target).collapse('toggle'); // Membuka atau menutup collapse yang dipilih
  //   });
  // });

  // Modal handling Data Bia
  var idDataHapusB;
  $("button#btn-hapus").click(function () {
    idDataHapusB = $(this).data("id");
    console.log(idDataHapusB);
    $("#idData").text(idDataHapusB);
    $("#modalHapus").modal("show");
  });

  $("#btnHapusB").click(function () {
    window.location.href = "bia/hapus/" + idDataHapusB;
  });

})(jQuery);

