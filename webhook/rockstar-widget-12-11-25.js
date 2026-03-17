(function(){
    let formHtml = `
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css"/>
        
        <style>
/* Overall Container */
.enquiry-widget {
    position: relative;
    font-family: "Segoe UI", Arial, sans-serif;
    width: 450px; /* increased width */
    max-width: 95%;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 24px;
    padding: 40px 32px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    animation: popup 0.35s ease-out;
    backdrop-filter: blur(12px);
    border: 1px solid rgba(200, 200, 200, 0.3);
}

/* Title */
.enquiry-widget h3 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 26px;
    font-weight: 700;
    color: #1d3557;
    letter-spacing: 0.5px;
}

/* Inputs */
.enquiry-widget input {
    width: 100%;
    margin: 14px 0;
    padding: 16px 18px;
    border: 1px solid #ccc;
    border-radius: 16px;
    font-size: 16px;
    background: #fafafa;
    transition: all 0.3s ease;
}

.iti { width: 100% !important; }
.iti input { width: 100% !important; padding-left: 100px !important; box-sizing: border-box; }

.enquiry-widget input:focus {
    border-color: #007BFF;
    outline: none;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.15);
}

/* Error messages */
.eq-error {
    color: #e63946;
    font-size: 13px;
    margin: -6px 0 10px 4px;
    display: block;
}

/* Submit Button */
.enquiry-widget button {
    width: 100%;
    margin-top: 18px;
    background: linear-gradient(135deg, #007BFF, #0056d2);
    color: #fff;
    border: none;
    padding: 16px;
    border-radius: 18px;
    font-size: 17px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s ease;
}

.enquiry-widget button:hover:not(:disabled) {
    background: linear-gradient(135deg, #0056d2, #003c99);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 91, 187, 0.35);
}

.enquiry-widget button:disabled {
    background: #bbb;
    cursor: not-allowed;
}

/* Success / Response */
#eq-response {
    margin-top: 14px;
    text-align: center;
    font-weight: 500;
    font-size: 14px;
    color: #2a9d8f;
}

/* Close button */
.close-icon {
    position: absolute;
    top: 16px;
    right: 16px;
    font-size: 26px;
    cursor: pointer;
    color: #555;
    transition: 0.2s;
}
.close-icon:hover {
    color: #000;
    transform: scale(1.25);
}

/* Animations */
@keyframes popup {
    from { transform: scale(0.85); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

/* Responsive */
@media (max-width: 768px) {
    .enquiry-widget {
        width: 100%;
        padding: 30px 20px;
    }
    .enquiry-widget h3 {
        font-size: 22px;
    }
    .enquiry-widget input {
        padding: 14px 16px;
    }
    .enquiry-widget button {
        padding: 14px;
        font-size: 16px;
    }
}

</style>

<div class="enquiry-widget">
    <span class="close-icon show-icon" id="modal-close">&times;</span>
    <h3>Sign Up Now</h3>
    <input type="text" id="eq-name" placeholder="Your Name" required />
    <div id="err-name" class="eq-error"></div>
    <input type="email" id="eq-email" placeholder="Your Email" required />
    <div id="err-email" class="eq-error"></div>
    <input type="tel" id="eq-phone" placeholder="Phone" required />
    <div id="err-phone" class="eq-error"></div>
    <button id="eq-submit">Submit</button>
    <div id="eq-response"></div>
</div>


        <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
    `;

    document.write(formHtml);

    function getParam(name) {
        return new URLSearchParams(window.location.search).get(name) || "";
    }

    document.addEventListener("DOMContentLoaded", function(){
        const btn = document.getElementById("eq-submit");
        const phoneInput = document.getElementById("eq-phone");

        // Initialize intl-tel-input
        const iti = window.intlTelInput(phoneInput, {
            initialCountry: "in",
            separateDialCode: true,
            preferredCountries: ["in", "us", "ae"],
            nationalMode: false, // ensures +91 format
        });
        
        document.addEventListener("DOMContentLoaded", function(){
    const modal = document.querySelector(".model-popup");
    const closeBtn = document.getElementById("modal-close");

    closeBtn.addEventListener("click", function(){
        modal.style.display = "none";
    });
});



      btn.addEventListener("click", function(){
    // clear errors
    document.getElementById("err-name").innerText = "";
    document.getElementById("err-email").innerText = "";
    document.getElementById("err-phone").innerText = "";
    document.getElementById("eq-response").innerText = "";

    let name = document.getElementById("eq-name").value.trim();
    let email = document.getElementById("eq-email").value.trim();

    // Build phone manually
    let countryCode = "+" + iti.getSelectedCountryData().dialCode;
    let rawPhone = phoneInput.value.trim();
    let phone = countryCode + rawPhone;

    let valid = true;

    // ✅ Name validation: only letters and spaces
    let nameRegex = /^[A-Za-z\s]+$/;
    if (!nameRegex.test(name) || name.length < 2) {
        document.getElementById("err-name").innerText = "Please enter a valid name (letters only).";
        valid = false;
    }

    // ✅ Email validation
    let emailRegex = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;
    if (!emailRegex.test(email)) {
        document.getElementById("err-email").innerText = "Please enter a valid email.";
        valid = false;
    }

    // ✅ Phone validation: exactly 10 digits
    let digits = rawPhone.replace(/\D/g, "");
    if (digits.length !== 10) {
        document.getElementById("err-phone").innerText = "Phone number must be exactly 10 digits.";
        valid = false;
    }

    if (!valid) return;

    // disable button
    btn.disabled = true;
    btn.innerText = "Submitting...";

    let data = new FormData();
    data.append("name", name);
    data.append("email", email);
    data.append("phone", phone); // +91XXXXXXXXXX

    // UTM tracking
    data.append("source", getParam("utm_source") || "none");
    data.append("medium", getParam("utm_medium") || "none");
    data.append("campaign", getParam("utm_campaign") || "none");
    data.append("gclid", getParam("gclid"));
    data.append("fbclid", getParam("fbclid"));
    data.append("ad_id", getParam("ad_id"));
    data.append("landing_page", window.location.href);
    data.append("page_url", window.location.origin + window.location.pathname);

    fetch("https://adsninja.adrescue.in/webhook/rockstar-leads-save.php", {
        method: "POST",
        body: data
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === "success") {
            document.getElementById("eq-response").innerText = "✅ Thank you! Redirecting...";
            document.getElementById("eq-response").className = "eq-success";
            setTimeout(() => {
                window.location.href = "https://school.rockstarshub.com/services/thankyou";
            }, 1500);
        } else {
            document.getElementById("eq-response").innerText = res.message;
            document.getElementById("eq-response").className = "eq-error";
            btn.disabled = false;
            btn.innerText = "Submit";
        }
    })
    .catch(err => {
        document.getElementById("eq-response").innerText = "❌ Server error!";
        document.getElementById("eq-response").className = "eq-error";
        btn.disabled = false;
        btn.innerText = "Submit";
    });
});

        
        
    });
})();
