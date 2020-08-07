$(document).ready(function(){
  function sleep(milliseconds) {
  	return new Promise(resolve => setTimeout(resolve, milliseconds))
  }
  // Sends invoice data to extension
  $("#form_invoice_fiscal").click(function(){
    chrome.runtime.sendMessage($("#settings_articles_id").val(), { launch: true });
    function connect(){

            // Generating needed invoice data
            var data = {};
            data.id = +(new Date());
            data.payment_type = $("#form_invoice_payment_type").val();
            data.price = 0;
            let services = [];
            $(".form_invoice_service").each(function(){
              if($(this).find(".form_invoice_service_code").length > 0){ // Filter only article services
                let service = {};
                let code = $(this).find(".form_invoice_service_code")[0].value;
                let name = $(this).find(".form_invoice_service_name_input")[0].value;
                let number = parseFloat($(this).find(".form_invoice_service_amount_input")[0].value);
                let total_price = (parseFloat($(this).find(".form_invoice_service_price_input")[0].value) + parseFloat($(this).find(".form_invoice_service_tax_input")[0].value)) * number;

                service["code"] = code;
                service["name"] = name;
                service["number"] = number;
                service["total_price"] = total_price;
                data.price += total_price;
                services.push(service);
              }
            });
            data.services = services;

            console.log(data);
            var port = chrome.runtime.connect($("#settings_articles_id").val(), { name: 'Serbian-Bookers' });
            port.postMessage({request : 'connect'});
            port.onMessage.addListener(function(message) {
                switch (message.answer){
                    case 'connected':
                        port.postMessage({request: 'invoice', data : data});
                        break;
                    case 'retry connection':
                        port.postMessage({request : 'connect'});
                        break;
                    case 'status':
                        $("#form_invoice_fiscal_message").text('STATUS: ' + message.state);
                        break;
                    case 'finished':
                        $("#form_invoice_fiscal_message").text('STATUS: Finished');
                        break;
                    case 'error':
                        $("#form_invoice_fiscal_message").text(message.state);
                        break;
                    default:
                        break;
                }
            });
    };
    sleep(5000).then(() => {
        connect();
    });
  });
});
