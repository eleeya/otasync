var id = null;
let email = null;
let callingcCode = null;
$(document).ready(function (){
    $.ajax({                                      
      url: 'https://ipapi.co/json/',              
      type: "GET",          
      data: "",
      success: function(response) {
        console.log(response.country_calling_code);
        $("#phone").val(response.country_calling_code)
        }
   });
});
function sendEmail(){
    
    let formValid = true;
    let firstName = $("#first-name").val();
    let lastName = $("#last-name").val();
    let phone = $("#phone").val();
    email = $("#email").val();
    let password = $("#password").val();
    let confirmPassword = $("#confirm-password").val();
    if(firstName == "" || !checkName(firstName)){
        $("#first-name").addClass("is-invalid");
        formValid = false;
    }
    else{
        $("#first-name").removeClass("is-invalid");
    }
    if(lastName == "" || !checkName(lastName)){
        $("#last-name").addClass("is-invalid");
        formValid = false;
    }
    else{
        $("#last-name").removeClass("is-invalid");
    }
    if(email == "" || !checkEmail(email)){
        $("#email").addClass("is-invalid");
        formValid = false;
    }
    else{
        $("#email").removeClass("is-invalid");
    }
    if(phone == "" || !checkPhone(phone)){
        $("#phone").addClass("is-invalid");
        formValid = false;
    }
    else{
        $("#phone").removeClass("is-invalid");
        
    }
    if(password.length < 6){
        $("#password").addClass("is-invalid");
        formValid = false;
    }
    else{
        $("#password").removeClass("is-invalid");

    }
    if(password != confirmPassword){
        $("#confirm-password").addClass("is-invalid");
        formValid = false;
    }
    else{
        $("#confirm-password").removeClass("is-invalid");

    }
    if(!$("#terms").is(':checked')){
        $("#terms").addClass("is-invalid");
        formValid = false;
    }
    else{
        $("#terms").removeClass("is-invalid");
    }
    if(formValid){
        var data = {
            name: firstName,
            surname: lastName,
            email: email,
            phone: phone,
            password: password
        }

        $.ajax({
            url: "https://admin.otasync.me/beta/api/account/register",
            method: "POST",
            data: data,
            success: function(response) {
                id = JSON.parse(response).id;
                console.log(id);
                
                if(id != null){
                    $("#registration-form").css("display","none");
                    $("#email-confirmation").css("display","block");
                }
                else{
                    alert("Error please try again");

                }
            },
            error: function(response){
                if(response.status == 400){
                    alert("Email already exist");
                }
                else{
                    alert("Error please try again");
                }
            }
        });
        
    }
}
function resendEmail(){
    var data = {
        id: id
    }
    $.ajax({
        url: "https://admin.otasync.me/beta/api/account/registerResend",
        method: "POST",
        data: data,
        success: function(response) {
            console.log(response)
            alert("Check your email " + email + " for requierd code");
        },
        error: function(response){
            console.log(response);
            alert("Error please try again");
        }
    });

}
function checkEmailCode(){
    let codeFromInput = $("#code").val();
        $("#code").removeClass("is-invalid");
        var data = {
            code: codeFromInput,
            id: id
        }
        $.ajax({
            url: "https://admin.otasync.me/beta/api/account/registerConfirm",
            method: "POST",
            data: data,
            success: function(response) {
                console.log(response);
                var status = JSON.parse(response).status;
                console.log(status);
                if(status == "ok"){
                    // go to qucik start 
                    $("#div-resend").css("display", "none");
                    $("#code").removeClass("is-invalid");
                    $("#code").addClass("is-valid");
                    $("#btn-quick-setup").css("display","block")

                }
                else{
                    $("#div-resend").css("display", "block");
                    $("#code").removeClass("is-valid");
                    $("#code").addClass("is-invalid");  
                }
            },
            error: function(response){
                console.log(response);
                alert("Error please try again");
            }
        });
    
        
    

}
function goToQuickSetup(){
    // redirect to Qucik Setup
}
function checkName(str) {
    return /^[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.'-]+$/u.test(
      str
    );
  }
function checkEmail(str) {
    // return /^(?:[a-z0-9!#$%&'+/=?^_`{|}~-]+(?:.[a-z0-9!#$%&'+/=?^_`{|}~-]+)|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\[\x01-\x09\x0b\x0c\x0e-\x7f])")@(?:(?:[a-z0-9](?:[a-z0-9-][a-z0-9])?.)+[a-z0-9](?:[a-z0-9-][a-z0-9])?|[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\[\x01-\x09\x0b\x0c\x0e-\x7f])+)])$/.test(
    //   str
    // );
    return true;
  }
function checkPhone(str) {
    return /^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s./0-9]+$/.test(str);
  }