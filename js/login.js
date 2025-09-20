const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const forgotPasswordLink = document.getElementById('forgotPassword');
const backToSignInLink = document.getElementById('backToSignIn');
const container = document.getElementById('container');

forgotPasswordLink.addEventListener('click', (e) => {
    e.preventDefault();
    container.classList.add("forgot-password-active");
    container.classList.remove("right-panel-active"); 
});

backToSignInLink.addEventListener('click', (e) => {
    e.preventDefault();
    container.classList.remove("forgot-password-active");
});

signUpButton.addEventListener('click', () => {
    container.classList.add("right-panel-active");
    container.classList.remove("forgot-password-active");
});

signInButton.addEventListener('click', () => {
    container.classList.remove("right-panel-active");
    container.classList.remove("forgot-password-active");
});

document.addEventListener("DOMContentLoaded", function () {
    const forgotPasswordBtn = document.querySelector("#forgot-password-btn");
    const backToSignInBtn = document.querySelector("#back-to-signin-btn");
    const container = document.querySelector(".container");

    forgotPasswordBtn.addEventListener("click", function () {
        container.classList.add("forgot-password-active");
    });

    backToSignInBtn.addEventListener("click", function () {
        container.classList.remove("forgot-password-active");
    });
});

document.addEventListener("DOMContentLoaded", function() {
    document.body.classList.add("loaded");
});
// After toggling signup
document.getElementById('signUp').addEventListener('click', () => {
    document.getElementById('container').classList.add('right-panel-active');
    setTimeout(() => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }, 500);
  });

  document.getElementById("viewDate").textContent = userData.formatted_date;

  