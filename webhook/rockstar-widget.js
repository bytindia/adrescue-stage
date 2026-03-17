function handleSubmit(id) {
    if (submissionInProgress.has(id)) return;

    const nameEl = document.getElementById(id + "-name");
    const emailEl = document.getElementById(id + "-email");
    const phoneEl = document.getElementById(id + "-phone");

    const errName = document.getElementById(id + "-err-name");
    const errEmail = document.getElementById(id + "-err-email");
    const errPhone = document.getElementById(id + "-err-phone");
    const response = document.getElementById(id + "-response");

    errName.innerText = "";
    errEmail.innerText = "";
    errPhone.innerText = "";
    response.innerText = "";
    response.style.color = "";

    let valid = true;

    const name = nameEl.value.trim();
    const email = emailEl.value.trim();
    const digits = phoneEl.value.replace(/\D/g, "");

    if (!/^[A-Za-z\s]{2,}$/.test(name)) {
        errName.innerText = "Enter a valid name.";
        valid = false;
    }

    if (!/^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/.test(email)) {
        errEmail.innerText = "Enter a valid email.";
        valid = false;
    }

    if (digits.length !== 10) {
        errPhone.innerText = "Phone must be 10 digits.";
        valid = false;
    }

    if (!valid) return;

    const iti = phoneEl._itiInstance;
    const code = "+" + iti.getSelectedCountryData().dialCode;
    const fullPhone = code + digits;

    const submitBtn = document.getElementById(id + "-submit");
    submitBtn.disabled = true;
    submitBtn.innerText = "Submitting...";

    submissionInProgress.add(id);

    /**
     * ✅ CTA SOURCE (SESSION SAFE)
     */
    let ctaSource =
        sessionStorage.getItem("rockstar_cta_source") ||
        "direct-widget-submit";

    /**
     * BUILD FORM DATA
     */
    const fd = new FormData();
    fd.append("name", name);
    fd.append("email", email);
    fd.append("phone", fullPhone);
    fd.append("form_id", id);
    fd.append("cta_source", ctaSource);

    // UTM / Ads
    fd.append("utm_source", getParam("utm_source"));
    fd.append("utm_medium", getParam("utm_medium"));
    fd.append("utm_campaign", getParam("utm_campaign"));
    fd.append("gclid", getParam("gclid"));
    fd.append("fbclid", getParam("fbclid"));
    fd.append("ad_id", getParam("ad_id"));
    fd.append("landing_page", window.location.href);
    fd.append("page_url", window.location.origin + window.location.pathname);

    fetch("https://adsninja.adrescue.in/webhook/rockstar-leads-save.php", {
        method: "POST",
        body: fd,
    })
        .then(res => res.json())
        .then(res => {
            submissionInProgress.delete(id);

            if (res.status === "success") {
                response.style.color = "#2a9d8f";
                response.innerText = "Thank you! Your details have been submitted successfully.";

                nameEl.value = "";
                emailEl.value = "";
                phoneEl.value = "";

                submitBtn.disabled = false;
                submitBtn.innerText = "Submit";

                // Clear CTA AFTER successful submit
                sessionStorage.removeItem("rockstar_cta_source");
                sessionStorage.removeItem("rockstar_cta_time");
            } else {
                response.style.color = "#d62828";
                response.innerText = res.message || "Submission failed.";
                submitBtn.disabled = false;
                submitBtn.innerText = "Submit";
            }
        })
        .catch(() => {
            submissionInProgress.delete(id);
            response.style.color = "#d62828";
            response.innerText = "Server error. Please try again.";
            submitBtn.disabled = false;
            submitBtn.innerText = "Submit";
        });
}
