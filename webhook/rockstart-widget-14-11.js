(function(){
    'use strict';
    
    // Track if dependencies are loaded
    let dependenciesLoaded = false;
    let initializedForms = new Set();
    let submissionInProgress = new Set();
    
    // Load CSS only once
    function loadCSS() {
        if (document.getElementById('rockstar-widget-css')) return;
        
        const link = document.createElement('link');
        link.id = 'rockstar-widget-css';
        link.rel = 'stylesheet';
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css';
        document.head.appendChild(link);
    }
    
    // Load JS only once
    function loadIntlTelInput(callback) {
        if (window.intlTelInput) {
            callback();
            return;
        }
        
        if (document.getElementById('rockstar-widget-intl-js')) {
            // Script is loading, wait for it
            const checkInterval = setInterval(() => {
                if (window.intlTelInput) {
                    clearInterval(checkInterval);
                    callback();
                }
            }, 50);
            return;
        }
        
        const script = document.createElement('script');
        script.id = 'rockstar-widget-intl-js';
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js';
        script.onload = callback;
        document.head.appendChild(script);
    }
    
    // Inject styles only once
    function injectStyles() {
        if (document.getElementById('rockstar-widget-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'rockstar-widget-styles';
        style.textContent = `
/* Overall Container */
.enquiry-widget {
    position: relative;
    font-family: "Segoe UI", Arial, sans-serif;
    width: 450px;
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
    box-sizing: border-box;
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
.eq-response {
    margin-top: 14px;
    text-align: center;
    font-weight: 500;
    font-size: 14px;
    color: #2a9d8f;
}

.eq-response.eq-error {
    color: #e63946;
}

/* Close button */
.close-icon {
    position: absolute;
    top: 16px;
    right: 16px;
    font-size: 28px;
    line-height: 1;
    cursor: pointer;
    color: #555;
    transition: 0.2s;
    z-index: 10;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 50%;
    font-weight: bold;
}
.close-icon:hover {
    color: #000;
    background: rgba(255, 255, 255, 1);
    transform: scale(1.15);
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
        `;
        document.head.appendChild(style);
    }
    
    // Get URL parameter
    function getParam(name) {
        return new URLSearchParams(window.location.search).get(name) || "";
    }
    
    // Initialize form in a container
    function initForm(containerId) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.warn('Rockstar Widget: Container #' + containerId + ' not found');
            return;
        }
        
        if (initializedForms.has(containerId)) {
            return; // Already initialized
        }
        
        // Create form HTML with unique IDs
        const formHtml = `
<div class="enquiry-widget">
    <span class="close-icon" data-close="${containerId}">&times;</span>
    <h3>Sign Up Now</h3>
    <input type="text" id="${containerId}-name" placeholder="Your Name" required />
    <div id="${containerId}-err-name" class="eq-error"></div>
    <input type="email" id="${containerId}-email" placeholder="Your Email" required />
    <div id="${containerId}-err-email" class="eq-error"></div>
    <input type="tel" id="${containerId}-phone" placeholder="Phone" required />
    <div id="${containerId}-err-phone" class="eq-error"></div>
    <button id="${containerId}-submit">Submit</button>
    <div id="${containerId}-response" class="eq-response"></div>
</div>
        `;
        
        container.innerHTML = formHtml;
        
        // Initialize intl-tel-input and store instance
        loadIntlTelInput(function() {
            const phoneInput = document.getElementById(containerId + '-phone');
            if (phoneInput) {
                const itiInstance = window.intlTelInput(phoneInput, {
                    initialCountry: "in",
                    separateDialCode: true,
                    preferredCountries: ["in", "us", "ae"],
                    nationalMode: false,
                });
                // Store instance on the input element for later access
                phoneInput._itiInstance = itiInstance;
            }
        });
        
        // Handle close button
        const closeBtn = container.querySelector('[data-close="' + containerId + '"]');
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Try multiple methods to close the popup (for different popup systems)
                let popupClosed = false;
                
                // Method 1: Find parent popup container
                let modal = container.closest('.model-popup, .popup, .modal, [class*="modal"], [class*="popup"], [class*="overlay"]');
                if (modal) {
                    modal.style.display = 'none';
                    popupClosed = true;
                }
                
                // Method 2: Find by common TagMango/overlay patterns
                if (!popupClosed) {
                    modal = container.closest('[id*="popup"], [id*="modal"], [id*="overlay"]');
                    if (modal) {
                        modal.style.display = 'none';
                        popupClosed = true;
                    }
                }
                
                // Method 3: Try to find and trigger close button in parent
                if (!popupClosed) {
                    const parentCloseBtn = container.closest('div').querySelector('.close, .close-btn, [class*="close"], button[class*="close"]');
                    if (parentCloseBtn) {
                        parentCloseBtn.click();
                        popupClosed = true;
                    }
                }
                
                // Method 4: Try to remove overlay/backdrop
                if (!popupClosed) {
                    const overlay = document.querySelector('.overlay, .backdrop, [class*="overlay"], [class*="backdrop"]');
                    if (overlay) {
                        overlay.style.display = 'none';
                    }
                }
            });
        }
        
        // Handle submit
        const submitBtn = document.getElementById(containerId + '-submit');
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                handleSubmit(containerId);
            });
        }
        
        initializedForms.add(containerId);
    }
    
    // Handle form submission
    function handleSubmit(containerId) {
        // Prevent duplicate submissions
        if (submissionInProgress.has(containerId)) {
            return;
        }
        
        const nameEl = document.getElementById(containerId + '-name');
        const emailEl = document.getElementById(containerId + '-email');
        const phoneEl = document.getElementById(containerId + '-phone');
        const submitBtn = document.getElementById(containerId + '-submit');
        const responseEl = document.getElementById(containerId + '-response');
        
        if (!nameEl || !emailEl || !phoneEl || !submitBtn || !responseEl) {
            return;
        }
        
        // Clear errors
        document.getElementById(containerId + '-err-name').innerText = '';
        document.getElementById(containerId + '-err-email').innerText = '';
        document.getElementById(containerId + '-err-phone').innerText = '';
        responseEl.innerText = '';
        responseEl.className = 'eq-response';
        
        let name = nameEl.value.trim();
        let email = emailEl.value.trim();
        
        // Get phone input value (national number only, since separateDialCode is true)
        const rawPhoneInput = phoneEl.value.trim();
        // Extract only digits from the input
        const phoneDigits = rawPhoneInput.replace(/\D/g, '');
        
        let valid = true;
        
        // Name validation
        let nameRegex = /^[A-Za-z\s]+$/;
        if (!nameRegex.test(name) || name.length < 2) {
            document.getElementById(containerId + '-err-name').innerText = 'Please enter a valid name (letters only).';
            valid = false;
        }
        
        // Email validation
        let emailRegex = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;
        if (!emailRegex.test(email)) {
            document.getElementById(containerId + '-err-email').innerText = 'Please enter a valid email.';
            valid = false;
        }
        
        // Phone validation - must be exactly 10 digits
        if (phoneDigits.length !== 10) {
            document.getElementById(containerId + '-err-phone').innerText = 'Phone number must be exactly 10 digits.';
            valid = false;
        }
        
        if (!valid) return;
        
        // Build full phone number with country code for submission
        let phone = '';
        if (phoneEl._itiInstance) {
            const itiInstance = phoneEl._itiInstance;
            const countryCode = '+' + itiInstance.getSelectedCountryData().dialCode;
            phone = countryCode + phoneDigits;
        } else if (window.intlTelInputGlobals && window.intlTelInputGlobals.getInstance) {
            // Fallback: try to get instance using globals API
            const itiInstance = window.intlTelInputGlobals.getInstance(phoneEl);
            if (itiInstance) {
                const countryCode = '+' + itiInstance.getSelectedCountryData().dialCode;
                phone = countryCode + phoneDigits;
            }
        }
        
        // If no instance found, use the digits as-is (fallback)
        if (!phone) {
            phone = phoneDigits;
        }
        
        // Mark as in progress
        submissionInProgress.add(containerId);
        submitBtn.disabled = true;
        submitBtn.innerText = 'Submitting...';
        
        // Prepare form data
        let data = new FormData();
        data.append('name', name);
        data.append('email', email);
        data.append('phone', phone);
        data.append('source', getParam('utm_source') || 'none');
        data.append('medium', getParam('utm_medium') || 'none');
        data.append('campaign', getParam('utm_campaign') || 'none');
        data.append('gclid', getParam('gclid') || '');
        data.append('fbclid', getParam('fbclid') || '');
        data.append('ad_id', getParam('ad_id') || '');
        data.append('landing_page', window.location.href);
        data.append('page_url', window.location.origin + window.location.pathname);
        
        // Submit
        fetch('https://adsninja.adrescue.in/webhook/rockstar-leads-save.php', {
            method: 'POST',
            body: data
        })
        .then(res => res.json())
        .then(res => {
            submissionInProgress.delete(containerId);
            
            if (res.status === 'success') {
                responseEl.innerText = '✅ Thank you! Redirecting...';
                responseEl.className = 'eq-response';
                setTimeout(() => {
                    window.location.href = 'https://school.rockstarshub.com/services/thankyou';
                }, 1500);
            } else {
                responseEl.innerText = res.message || 'An error occurred. Please try again.';
                responseEl.className = 'eq-response eq-error';
                submitBtn.disabled = false;
                submitBtn.innerText = 'Submit';
            }
        })
        .catch(err => {
            submissionInProgress.delete(containerId);
            responseEl.innerText = '❌ Server error! Please try again.';
            responseEl.className = 'eq-response eq-error';
            submitBtn.disabled = false;
            submitBtn.innerText = 'Submit';
        });
    }
    
    // Initialize all forms on page load
    function initAllForms() {
        const containers = document.querySelectorAll('.widget-form');
        containers.forEach(function(container) {
            if (container.id) {
                initForm(container.id);
            }
        });
    }
    
    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            loadCSS();
            injectStyles();
            initAllForms();
        });
    } else {
        loadCSS();
        injectStyles();
        initAllForms();
    }
    
    // Expose init function for manual initialization
    window.RockstarWidget = {
        init: function(containerId) {
            loadCSS();
            injectStyles();
            loadIntlTelInput(function() {
                initForm(containerId);
            });
        },
        initAll: function() {
            loadCSS();
            injectStyles();
            loadIntlTelInput(function() {
                initAllForms();
            });
        }
    };
})();
