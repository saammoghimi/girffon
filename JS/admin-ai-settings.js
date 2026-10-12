document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const form = document.getElementById('adminAiSettingsForm');
    if (!form) return;

    const endpoint = 'backend/admin/ai-settings.php';
    const featureEnabled = document.getElementById('adminAiFeatureEnabled');
    const generationEnabled = document.getElementById('adminAiGenerationEnabled');
    const packagePrice = document.getElementById('adminAiPackagePrice');
    const packageGenerations = document.getElementById('adminAiPackageGenerations');
    const status = document.getElementById('adminAiSettingsStatus');
    const packagesSold = document.getElementById('adminAiPackagesSold');
    const revenue = document.getElementById('adminAiRevenue');
    const creditsPurchased = document.getElementById('adminAiCreditsPurchased');
    const creditsUsed = document.getElementById('adminAiCreditsUsed');
    const submitButton = form.querySelector('button[type="submit"]');

    function setStatus(message, success) {
        status.textContent = message;
        status.classList.toggle('is-success', Boolean(success));
        status.classList.toggle('is-error', success === false);
    }

    function render(data) {
        const settings = data.settings || {};
        const counters = data.counters || {};
        featureEnabled.value = settings.feature_enabled ? '1' : '0';
        generationEnabled.value = settings.generation_enabled ? '1' : '0';
        packagePrice.value = Number(settings.package_price || 0).toFixed(2);
        packageGenerations.value = String(settings.package_generations || '');
        packagesSold.textContent = String(counters.packages_sold || 0);
        revenue.textContent = '€' + Number(counters.ai_revenue || 0).toFixed(2);
        creditsPurchased.textContent = String(counters.credits_purchased || 0);
        creditsUsed.textContent = String(counters.credits_used || 0);
    }

    async function request(options) {
        const response = await fetch(endpoint, options);
        const data = await response.json();
        if (!response.ok || !data.ok) {
            throw new Error(data.message || 'Unable to load GIRFFON AI settings.');
        }
        return data;
    }

    async function load() {
        setStatus('Loading GIRFFON AI settings...');
        try {
            const data = await request({ headers: { Accept: 'application/json' } });
            render(data);
            setStatus('GIRFFON AI settings loaded.', true);
        } catch (error) {
            setStatus(error.message, false);
        }
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        submitButton.disabled = true;
        setStatus('Saving GIRFFON AI settings...');

        try {
            const data = await request({
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    feature_enabled: featureEnabled.value === '1',
                    generation_enabled: generationEnabled.value === '1',
                    package_price: packagePrice.value,
                    package_generations: packageGenerations.value
                })
            });
            render(data);
            setStatus(data.message, true);
        } catch (error) {
            setStatus(error.message, false);
        } finally {
            submitButton.disabled = false;
        }
    });

    load();
});
