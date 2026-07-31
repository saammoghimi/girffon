(function () {
  const FORM_ID = 'gfGiftCardForm';
  const STATUS_ID = 'gfGiftCardStatus';
  const SUBMIT_ID = 'gfGiftCardSubmit';
  const ADD_GIFT_CARD_URL = '/GirffoN/backend/gift-cards/add-gift-card.php';
  const CSRF_TOKEN_URL = '/GirffoN/backend/auth/csrf-token.php';
  const CART_REDIRECT_URL = '/GirffoN/CartTest.html';

  let csrfToken = '';

  async function readCsrfToken(forceRefresh) {
    if (!forceRefresh && csrfToken) {
      return csrfToken;
    }

    const response = await fetch(CSRF_TOKEN_URL, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const payload = await response.json();

    if (!response.ok || !payload.ok || !payload.csrf_token) {
      throw new Error('Unable to prepare a secure request.');
    }

    csrfToken = String(payload.csrf_token || '');
    return csrfToken;
  }

  function setStatus(message, state) {
    const node = document.getElementById(STATUS_ID);
    if (!node) {
      return;
    }

    node.textContent = String(message || '');
    node.classList.remove('is-success', 'is-error');
    if (state === 'success') {
      node.classList.add('is-success');
    }
    if (state === 'error') {
      node.classList.add('is-error');
    }
  }

  function selectedAmount(form) {
    const checkedPreset = form.querySelector('input[name="gift_amount_preset"]:checked');
    const customInput = form.querySelector('input[name="custom_amount"]');
    const customValue = Number(customInput && customInput.value ? customInput.value : 0);

    if (checkedPreset && checkedPreset.value === 'custom') {
      return customValue;
    }

    if (customInput && customInput.value.trim() !== '' && (!checkedPreset || checkedPreset.value === 'custom')) {
      return customValue;
    }

    return Number(checkedPreset ? checkedPreset.value : 25);
  }

  async function handleSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    const submitButton = document.getElementById(SUBMIT_ID);
    const formData = new FormData(form);
    const amount = selectedAmount(form);
    const payload = {
      amount: amount,
      delivery_type: String(formData.get('delivery_type') || 'digital'),
      buyer_name: String(formData.get('buyer_name') || '').trim(),
      buyer_email: String(formData.get('buyer_email') || '').trim(),
      recipient_name: String(formData.get('recipient_name') || '').trim(),
      recipient_email: String(formData.get('recipient_email') || '').trim(),
      gift_message: String(formData.get('gift_message') || '').trim(),
      expires_at: String(formData.get('expires_at') || '').trim()
    };

    if (!payload.buyer_name || !payload.buyer_email || !payload.recipient_name || !payload.recipient_email) {
      setStatus('Complete buyer and recipient details before adding the gift card to the cart.', 'error');
      return;
    }

    if (!(amount > 0)) {
      setStatus('Choose a valid gift card amount.', 'error');
      return;
    }

    if (submitButton) {
      submitButton.disabled = true;
    }

    try {
      const token = await readCsrfToken(false);
      const response = await fetch(ADD_GIFT_CARD_URL, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-Girffon-Csrf': token
        },
        body: JSON.stringify(payload)
      });
      const result = await response.json();

      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Unable to add the gift card to the cart.');
      }

      setStatus('Gift card added to the cart. Redirecting to checkout now.', 'success');
      window.setTimeout(function () {
        window.location.href = CART_REDIRECT_URL;
      }, 700);
    } catch (error) {
      setStatus(error instanceof Error ? error.message : 'Unable to add the gift card to the cart.', 'error');
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
      }
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById(FORM_ID);
    if (!form) {
      return;
    }

    form.addEventListener('submit', handleSubmit);
  });
}());
