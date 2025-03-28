function validateForm(passwordId, errorId) {
    let password = document.getElementById(passwordId).value;
    let errorElement = document.getElementById(errorId);
    let minlength = 8; //set mininum password length (8)
    let isValid = true;

    // Clear error messages
    errorElement.innerHTML = "";

    if (!password) {
        errorElement.innerHTML = "Password is required.";
        isValid = false;
    } else if (password.length < minlength) {
        errorElement.innerHTML = `Please enter a password that is at least ${minlength} characters.`;
        isValid = false;
    } else {
        const hasCapitalLetter = /[A-Z]/; //Define Capital Letter [A-Z]
        const hasNumber = /\d/; //Define digit [0-9]
        const hasSpecialCharacter = /[!@#$%^&*()-+.]/; //Define Special Character [!@#$%^&*()-+.]

        if (!hasCapitalLetter.test(password)) {
            errorElement.innerHTML = "Please use at least one Capital Letter.";
            isValid = false;
        }
        if (!hasNumber.test(password)) {
            errorElement.innerHTML = "Please use at least one Number.";
            isValid = false;
        }
        if (!hasSpecialCharacter.test(password)) {
            errorElement.innerHTML = "Please use at least one Special Character.";
            isValid = false;
        }
    }
    return isValid;

    
}

//to show password when click the show password checkbox button
function togglePasswordVisibility(checkbox, passwordId) {
    const passwordInput = document.getElementById(passwordId);
    passwordInput.type = checkbox.checked ? 'text' : 'password';
}