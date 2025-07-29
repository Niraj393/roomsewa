// assets/js/payment.js

document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    document.querySelectorAll('.method-tabs .tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons and tabs
            document.querySelectorAll('.method-tabs .tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Show corresponding tab
            const tabId = this.getAttribute('data-tab') + '-tab';
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Format card number input
    const cardNumberInput = document.getElementById('card_number');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/\s/g, '').replace(/(\d{4})/g, '$1 ').trim();
        });
    }
    
    // Format expiry date input
    const expiryInput = document.getElementById('expiry');
    if (expiryInput) {
        expiryInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').replace(/(\d{2})(\d{0,2})/, '$1/$2');
        });
    }
    
    // Format Khalti mobile number
    const khaltiMobileInput = document.getElementById('khalti_mobile');
    if (khaltiMobileInput) {
        khaltiMobileInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 10);
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('.payment-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Add any additional client-side validation here
            return true;
        });
    });
});