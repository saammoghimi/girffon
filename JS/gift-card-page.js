(function () {
  const FORM_ID = 'gfGiftCardForm';
  const STATUS_ID = 'gfGiftCardStatus';
  const SUBMIT_ID = 'gfGiftCardSubmit';
  const ADD_GIFT_CARD_URL = '/GirffoN/backend/gift-cards/add-gift-card.php';
  const CSRF_TOKEN_URL = '/GirffoN/backend/auth/csrf-token.php';
  const CART_REDIRECT_URL = '/GirffoN/CartTest.html';
  const CARD_SELECTOR = '[data-gift-card-select]';

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

  function syncSelectedCards(form, amountValue) {
    const selectedValue = String(amountValue || '');
    document.querySelectorAll('.gift-card-shop-card').forEach(function (card) {
      card.classList.toggle('is-selected', String(card.getAttribute('data-gift-card-select') || '') === selectedValue);
    });

    const presetInput = form.querySelector('input[name="gift_amount_preset"][value="' + selectedValue + '"]');
    if (presetInput) {
      presetInput.checked = true;
    }
  }

  function applyPresetAmount(form, amountValue) {
    const value = String(amountValue || '').trim();
    const customInput = form.querySelector('input[name="custom_amount"]');
    const customPreset = form.querySelector('input[name="gift_amount_preset"][value="custom"]');
    const presetInput = form.querySelector('input[name="gift_amount_preset"][value="' + value + '"]');

    if (presetInput) {
      presetInput.checked = true;
      if (customInput) {
        customInput.value = '';
      }
      syncSelectedCards(form, value);
      return;
    }

    if (customPreset) {
      customPreset.checked = true;
    }
    if (customInput && value !== '') {
      customInput.value = value;
    }
    syncSelectedCards(form, '');
  }

  function applyQueryAmount(form) {
    const params = new URLSearchParams(window.location.search);
    const amount = params.get('amount');
    if (!amount) {
      syncSelectedCards(form, selectedAmount(form));
      return;
    }

    applyPresetAmount(form, amount);
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
      gift_message: String(formData.get('gift_message') || '').trim()
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

      if (window.GirffonAnalytics && typeof window.GirffonAnalytics.track === 'function') {
        window.GirffonAnalytics.track('add_to_cart', {
          item_type: 'gift_card',
          delivery_type: payload.delivery_type,
          amount: amount
        });
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

    if (window.GirffonAnalytics && typeof window.GirffonAnalytics.trackOnce === 'function') {
      window.GirffonAnalytics.trackOnce('gift_card_view', 'gift-card-view', {
        page: 'gift-card'
      });
    }

    applyQueryAmount(form);

    document.querySelectorAll(CARD_SELECTOR).forEach(function (node) {
      node.addEventListener('click', function (event) {
        const amount = node.getAttribute('data-gift-card-select');
        if (!amount) {
          return;
        }
        if (event.target && event.target.closest('.gift-card-shop-button')) {
          event.preventDefault();
        }
        applyPresetAmount(form, amount);
      });

      if (node.classList.contains('gift-card-shop-card')) {
        node.addEventListener('keydown', function (event) {
          if (event.key !== 'Enter' && event.key !== ' ') {
            return;
          }
          event.preventDefault();
          applyPresetAmount(form, node.getAttribute('data-gift-card-select'));
        });
      }
    });

    form.querySelectorAll('input[name="gift_amount_preset"]').forEach(function (input) {
      input.addEventListener('change', function () {
        syncSelectedCards(form, input.value === 'custom' ? '' : input.value);
      });
    });

    const customInput = form.querySelector('input[name="custom_amount"]');
    const customPreset = form.querySelector('input[name="gift_amount_preset"][value="custom"]');
    if (customInput && customPreset) {
      customInput.addEventListener('focus', function () {
        customPreset.checked = true;
        syncSelectedCards(form, '');
      });

      customInput.addEventListener('input', function () {
        if (customInput.value.trim() !== '') {
          customPreset.checked = true;
          syncSelectedCards(form, '');
        }
      });
    }

    form.addEventListener('submit', handleSubmit);
  });
}());
