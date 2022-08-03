let email2 = document.getElementById("email");
let loginBtn = document.getElementById("login-btn");
let password2 = document.getElementById("passwords")

const validate =(e)=> {
    let re = /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/;
    let passre = /^[a-zA-Z0-9]{8,}$/;
    e.preventDefault()
    if (re.test(email2.value)) {
        email2.removeAttribute( 'style', 'border:1px solid red !important' );

    }
    else {
        email2.setAttribute( 'style', 'border:1px solid red !important' );

    }

    if (passre.test(password2.value)) {
        password2.removeAttribute( 'style', 'border:1px solid red !important' );
    }
    else {
        password2.setAttribute( 'style', 'border:1px solid red !important' );
    }

}

loginBtn.addEventListener('click',validate)
