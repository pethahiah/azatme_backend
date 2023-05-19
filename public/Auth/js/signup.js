let email = document.getElementById("email");
let password = document.getElementById("passwords");
let confirm_password = document.getElementById("confirm_password");
let category = document.getElementById("category");
let terms = document.getElementById("terms");
let btn = document.getElementById("create-btn");



const checkBox = (e)=>{

    if (terms.checked === true) {
        btn.style.color = "#ffffff";
        btn.style.backgroundColor = "#215EAA";
        btn.disabled = false
    }else{
        btn.style.color = "#c4c4c4";
        btn.style.backgroundColor = "#f7f6f6";
        btn.disabled = true
    }

}

terms.onchange = function(){
    checkBox()
}

const register = (e) => {

  if (password.value !== confirm_password.value) {
    confirm_password.setAttribute( 'style', 'border:1px solid red !important' );
  }else{
    confirm_password.removeAttribute( 'style', 'border:1px solid red !important' );
  }

  if(password.value.length < 8 || password.value===""){
    password.setAttribute( 'style', 'border:1px solid red !important' );
  }else{
    password.removeAttribute( 'style', 'border:1px solid red !important' );
  }

  if(email.value === "" ){
    email.setAttribute( 'style', 'border:1px solid red !important' );
  }else{
    email.removeAttribute( 'style', 'border:1px solid red !important' );
  }

  if(category.value===""){
    category.setAttribute( 'style', 'border:1px solid red !important' );
  }else{
    category.removeAttribute( 'style', 'border:1px solid red !important' );
  }

  e.preventDefault()

};

btn.addEventListener("click", register);
