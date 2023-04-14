jQuery(document).ready(function ($) {
  // Öppna plocklista-modalen när knappen klickas
  $("#solvo-generate-plocklista").on("click", function () {
    var order_id = $("input[name=order_id]").val();
    if (order_id) {
      // Kontrollera om order_id är definierad och inte tom
      // Skicka AJAX-begäran
      var data = {
        action: "solvo_generate_pdf",
        order_id: order_id,
      };

      $.post(solvo_ajax.ajax_url, data, function (response) {
        // Ladda ner PDF-filen när AJAX-responsen tas emot
        // console.log("RESPONSE", response.data);
        window.open(response.data.url);

        var iframe;

        iframe = document.createElement("iframe");
        iframe.src = response.data.url;
        iframe.style.display = "none";
        iframe.id = "solvo-print";
        document.body.appendChild(iframe);

        $("#solvo-print").get(0).contentWindow.print();
      });
    } else {
      alert("Ingen order hittades.");
    }
  });
});
